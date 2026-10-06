<?php

namespace App\Services;

use App\Enums\Kategorie;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Models\Zahlung;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Einzige Stelle, an der Spendenbeträge berechnet und verteilt werden.
 *
 * Regeln (alle Beträge intern in Cent):
 *  - Betrag eines Sponsorings = Festbetrag + Runden × Rundenbetrag, gedeckelt durch den Maximalbetrag (falls > 0).
 *    Bei Team-Sponsorings zählen die Runden aller Teammitglieder.
 *  - Soziale Abgabe und Klassenkasse werden beide vom Bruttobetrag berechnet:
 *      Abgabe      = Brutto × Abgabe-%
 *      Klassenkasse = Schüleranteil × Klassen-% (Team-Spenden anteilig nach Runden der Mitglieder)
 *  - Der Rest wird gleichmäßig auf die gewählten Projekte verteilt (ohne Auswahl: alle Projekte des Events).
 *  - Rundungsreste werden nach dem Largest-Remainder-Verfahren verteilt, Summen stimmen centgenau.
 */
class SpendenRechner
{
    private const CACHE_VERSION_KEY = 'spendenrechner.version';

    /** @var Collection<int, Laeufer>|null */
    private ?Collection $laeufer = null;

    /** @var Collection<int, Collection<int, Laeufer>>|null */
    private ?Collection $teamMitglieder = null;

    /** @var list<int>|null */
    private ?array $eventProjekte = null;

    /** @var array<int, true>|null */
    private ?array $teamIds = null;

    public function __construct(private readonly Event $event) {}

    public static function fuer(Event $event): self
    {
        return new self($event);
    }

    // ---------------------------------------------------------------------
    // Grundrechenarten
    // ---------------------------------------------------------------------

    public static function cent(mixed $euro): int
    {
        if ($euro === null || $euro === '') {
            return 0;
        }

        return (int) round(((float) $euro) * 100);
    }

    public static function betragCent(int $runden, mixed $rundenBetrag, mixed $festBetrag, mixed $maxBetrag): int
    {
        $summe = self::cent($festBetrag) + max(0, $runden) * self::cent($rundenBetrag);
        $max = $maxBetrag === null || $maxBetrag === '' ? 0 : self::cent($maxBetrag);

        if ($max > 0 && $summe > $max) {
            return $max;
        }

        return $summe;
    }

    /**
     * Verteilt einen Betrag im Verhältnis der Gewichte (Largest-Remainder).
     * Sind alle Gewichte 0, wird gleichmäßig verteilt.
     *
     * @param  array<int|string, int|float>  $gewichte
     * @return array<int|string, int>
     */
    public static function verteile(int $betrag, array $gewichte): array
    {
        if ($gewichte === []) {
            return [];
        }

        $summe = array_sum($gewichte);
        if ($summe <= 0) {
            $gewichte = array_fill_keys(array_keys($gewichte), 1);
            $summe = count($gewichte);
        }

        $ergebnis = [];
        $reste = [];
        foreach ($gewichte as $key => $gewicht) {
            $exakt = $betrag * $gewicht / $summe;
            $ergebnis[$key] = (int) floor($exakt);
            $reste[$key] = $exakt - $ergebnis[$key];
        }

        $rest = $betrag - array_sum($ergebnis);
        arsort($reste);
        foreach (array_keys($reste) as $key) {
            if ($rest <= 0) {
                break;
            }
            $ergebnis[$key]++;
            $rest--;
        }

        return $ergebnis;
    }

    public static function prozent(int $betrag, mixed $prozent): int
    {
        return (int) round($betrag * (float) $prozent / 100);
    }

    // ---------------------------------------------------------------------
    // Cache
    // ---------------------------------------------------------------------

    /** Verwirft alle zwischengespeicherten Bilanzen (nach Änderungen an Runden, Sponsorings, ...). */
    public static function vergessen(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, (string) Str::uuid());
    }

    public function bilanz(): EventBilanz
    {
        $version = Cache::rememberForever(self::CACHE_VERSION_KEY, fn () => (string) Str::uuid());

        return Cache::remember("spendenrechner.bilanz.{$this->event->id}.$version", now()->addMinutes(10), fn () => $this->berechne());
    }

    // ---------------------------------------------------------------------
    // Berechnung
    // ---------------------------------------------------------------------

    public function berechne(): EventBilanz
    {
        $bilanz = new EventBilanz;

        foreach ($this->laeuferListe() as $laeufer) {
            $bilanz->laeufer[$laeufer->id] = ['spende' => 0, 'einzel' => 0, 'teamanteil' => 0, 'sponsorings' => 0];
        }

        $sponsorings = Sponsoring::forEvent($this->event)->with(['projects' => fn ($q) => $q->select('projects.id')])->get();

        foreach ($sponsorings as $sponsoring) {
            $aufteilung = $this->aufteilung($sponsoring);
            if ($aufteilung === null) {
                continue;
            }

            $bilanz->sponsorings[$sponsoring->id] = $aufteilung->brutto;
            $bilanz->bruttoCent += $aufteilung->brutto;
            $bilanz->festCent += $aufteilung->fest;
            $bilanz->rundenCent += $aufteilung->brutto - $aufteilung->fest;
            $bilanz->abgabeCent += $aufteilung->abgabe;

            $sponsor = $bilanz->sponsoren[$sponsoring->sponsor_id] ??= ['soll' => 0, 'sponsorings' => 0];
            $bilanz->sponsoren[$sponsoring->sponsor_id] = [
                'soll' => $sponsor['soll'] + $aufteilung->brutto,
                'sponsorings' => $sponsor['sponsorings'] + 1,
            ];

            if ($sponsoring->istTeamSponsoring()) {
                $team = $bilanz->teams[$sponsoring->sponsorable_id] ??= ['spende' => 0, 'sponsorings' => 0];
                $bilanz->teams[$sponsoring->sponsorable_id] = [
                    'spende' => $team['spende'] + $aufteilung->brutto,
                    'sponsorings' => $team['sponsorings'] + 1,
                ];
            }

            foreach ($aufteilung->laeufer as $laeuferId => $anteil) {
                $eintrag = &$bilanz->laeufer[$laeuferId];
                $eintrag['spende'] += $anteil;
                if ($sponsoring->istTeamSponsoring()) {
                    $eintrag['teamanteil'] += $anteil;
                } else {
                    $eintrag['einzel'] += $anteil;
                    $eintrag['sponsorings']++;
                }
                unset($eintrag);
            }

            foreach ($aufteilung->klassen as $klasseId => $werte) {
                $klasse = $bilanz->klassen[$klasseId] ?? ['brutto' => 0, 'kasse' => 0];
                $bilanz->klassen[$klasseId] = [
                    'brutto' => $klasse['brutto'] + $werte['brutto'],
                    'kasse' => $klasse['kasse'] + $werte['kasse'],
                ];
                $bilanz->klassenkasseCent += $werte['kasse'];
                $bilanz->klassenSponsoren[$klasseId][$sponsoring->sponsor_id] =
                    ($bilanz->klassenSponsoren[$klasseId][$sponsoring->sponsor_id] ?? 0) + $werte['brutto'];
            }

            foreach ($aufteilung->projekte as $projektId => $betrag) {
                $bilanz->projekte[$projektId] = ($bilanz->projekte[$projektId] ?? 0) + $betrag;
                $bilanz->projekteCent += $betrag;
            }
        }

        $bilanz->zahlungen = Zahlung::forEvent($this->event)
            ->selectRaw('sponsor_id, SUM(betrag) as summe')
            ->groupBy('sponsor_id')
            ->pluck('summe', 'sponsor_id')
            ->map(fn ($summe) => self::cent($summe))
            ->all();
        $bilanz->bezahltCent = array_sum($bilanz->zahlungen);

        return $bilanz;
    }

    /**
     * Aufteilung eines Sponsorings. Liefert null, wenn das Ziel (Läufer/Team) nicht mehr existiert.
     */
    public function aufteilung(Sponsoring $sponsoring): ?SponsoringAufteilung
    {
        $laeufer = $this->laeuferListe();

        if ($sponsoring->istTeamSponsoring()) {
            if (! isset($this->teamIds()[$sponsoring->sponsorable_id])) {
                return null;
            }
            $mitglieder = $this->teamMitglieder()->get($sponsoring->sponsorable_id, collect());
            $runden = (int) $mitglieder->sum('runden');
        } else {
            $einzel = $laeufer->get($sponsoring->sponsorable_id);
            if ($einzel === null) {
                return null;
            }
            $mitglieder = collect([$einzel]);
            $runden = (int) $einzel->runden;
        }

        $brutto = self::betragCent($runden, $sponsoring->rundenBetrag, $sponsoring->festBetrag, $sponsoring->maxBetrag);
        $fest = min($brutto, self::cent($sponsoring->festBetrag));

        // Anteile der Läufer (bei Teams nach Runden)
        $anteile = self::verteile($brutto, $mitglieder->mapWithKeys(fn (Laeufer $l) => [$l->id => (int) $l->runden])->all());

        // Klassenkasse: Schüleranteile je Klasse
        $klassen = [];
        foreach ($anteile as $laeuferId => $anteil) {
            $l = $laeufer->get($laeuferId);
            if ($l && $l->kategorie === Kategorie::Schueler && $l->schulklasse_id) {
                $klassen[$l->schulklasse_id]['brutto'] = ($klassen[$l->schulklasse_id]['brutto'] ?? 0) + $anteil;
            }
        }
        foreach ($klassen as $id => $werte) {
            $klassen[$id]['kasse'] = $this->event->hatKlassenkasse()
                ? self::prozent($werte['brutto'], $this->event->klassenkasse_prozent)
                : 0;
        }

        $abgabe = $this->event->hatSozialeAbgabe() ? self::prozent($brutto, $this->event->soziale_abgabe_prozent) : 0;
        $rest = $brutto - $abgabe - array_sum(array_column($klassen, 'kasse'));

        $projektIds = $sponsoring->relationLoaded('projects')
            ? $sponsoring->projects->pluck('id')->all()
            : $sponsoring->projects()->pluck('projects.id')->all();
        if ($projektIds === []) {
            $projektIds = $this->eventProjekte();
        }
        $projekte = $projektIds === []
            ? [0 => $rest]
            : self::verteile($rest, array_fill_keys($projektIds, 1));

        return new SponsoringAufteilung($brutto, $fest, $abgabe, $anteile, $klassen, $projekte);
    }

    public function sponsorKonto(Sponsor $sponsor): SponsorKonto
    {
        $bilanz = $this->bilanz();

        return new SponsorKonto($bilanz->sponsorSoll($sponsor->id), $bilanz->sponsorIst($sponsor->id));
    }

    // ---------------------------------------------------------------------
    // Daten
    // ---------------------------------------------------------------------

    /** @return Collection<int, Laeufer> */
    private function laeuferListe(): Collection
    {
        return $this->laeufer ??= Laeufer::forEvent($this->event)
            ->get(['id', 'event_id', 'team_id', 'runden', 'kategorie', 'schulklasse_id'])
            ->keyBy('id');
    }

    /** @return Collection<int, Collection<int, Laeufer>> */
    private function teamMitglieder(): Collection
    {
        return $this->teamMitglieder ??= $this->laeuferListe()->whereNotNull('team_id')->groupBy('team_id');
    }

    /** @return array<int, true> */
    private function teamIds(): array
    {
        return $this->teamIds ??= Teams::forEvent($this->event)->pluck('id')->flip()->map(fn () => true)->all();
    }

    /** @return list<int> */
    private function eventProjekte(): array
    {
        return $this->eventProjekte ??= $this->event->projects()->orderBy('projects.id')->pluck('projects.id')->all();
    }
}
