<?php

namespace App\Services;

use App\Enums\Kategorie;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Schulklasse;
use App\Models\Sponsor;
use App\Models\Teams;
use Illuminate\Support\Collection;

/**
 * Kennzahlen und Auswertungen einer Veranstaltung. Alle Beträge kommen aus dem SpendenRechner (Cent).
 */
class Statistik
{
    public const DIMENSIONEN = [
        'altersgruppe' => 'Altersgruppe',
        'geschlecht' => 'Geschlecht',
        'kategorie' => 'Kategorie',
        'klasse' => 'Klasse',
        'stufe' => 'Klassenstufe',
        'team' => 'Team',
        'teilnahme' => 'Einzel / Team',
    ];

    public const GESCHLECHTER = [
        'weiblich' => 'weiblich',
        'maennlich' => 'männlich',
        'divers' => 'divers',
    ];

    private readonly EventBilanz $bilanz;

    private ?Collection $zeilen = null;

    public function __construct(private readonly Event $event, ?EventBilanz $bilanz = null)
    {
        $this->bilanz = $bilanz ?? SpendenRechner::fuer($event)->bilanz();
    }

    public static function fuer(Event $event): self
    {
        return new self($event);
    }

    public function bilanz(): EventBilanz
    {
        return $this->bilanz;
    }

    public function event(): Event
    {
        return $this->event;
    }

    /**
     * Eine Zeile je Läufer mit allen Dimensionen und Kennzahlen.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function laeuferZeilen(): Collection
    {
        if ($this->zeilen !== null) {
            return $this->zeilen;
        }

        $teams = Teams::forEvent($this->event)->pluck('name', 'id');
        $klassen = Schulklasse::forEvent($this->event)->get()->keyBy('id');
        $altersgruppen = collect($this->event->altersgruppenListe())->pluck('key')->flip();

        return $this->zeilen = Laeufer::forEvent($this->event)
            ->orderBy('nachname')->orderBy('vorname')
            ->get()
            ->map(function (Laeufer $l) use ($teams, $klassen, $altersgruppen) {
                $alter = $l->geburtsdatum ? (int) $l->geburtsdatum->diffInYears($this->event->datum) : null;
                $gruppe = $this->event->altersgruppeFuer($alter);
                $klasse = $l->schulklasse_id ? $klassen->get($l->schulklasse_id) : null;
                $werte = $this->bilanz->laeufer[$l->id] ?? ['spende' => 0, 'einzel' => 0, 'teamanteil' => 0, 'sponsorings' => 0];

                return [
                    'id' => $l->id,
                    'startnummer' => $l->startnummer,
                    'name' => $l->name,
                    'vorname' => $l->vorname,
                    'nachname' => $l->nachname,
                    'kurzname' => $l->kurzname,
                    'alter' => $alter,
                    'altersgruppe' => $gruppe['label'],
                    'altersgruppe_key' => $gruppe['key'],
                    'altersgruppe_sort' => $altersgruppen[$gruppe['key']] ?? 999,
                    'geschlecht' => self::GESCHLECHTER[$l->geschlecht] ?? 'keine Angabe',
                    'geschlecht_key' => $l->geschlecht ?: 'unbekannt',
                    'kategorie' => $l->kategorie?->label() ?? 'keine Angabe',
                    'kategorie_key' => $l->kategorie?->value ?? 'unbekannt',
                    'klasse' => $klasse?->name ?? '–',
                    'klasse_id' => $klasse?->id,
                    'stufe' => $klasse?->stufe,
                    'team' => $l->team_id ? ($teams[$l->team_id] ?? '–') : '–',
                    'team_id' => $l->team_id,
                    'teilnahme' => $l->team_id ? 'im Team' : 'einzeln',
                    'runden' => (int) $l->runden,
                    'km' => $this->event->km((int) $l->runden),
                    'spende' => $werte['spende'],
                    'einzel' => $werte['einzel'],
                    'teamanteil' => $werte['teamanteil'],
                    'sponsorings' => $werte['sponsorings'],
                ];
            });
    }

    /**
     * Kennzahlen einer Menge von Läufer-Zeilen.
     *
     * @param  Collection<int, array<string, mixed>>  $zeilen
     * @return array<string, int|float>
     */
    public function kennzahlen(Collection $zeilen): array
    {
        $teilnehmer = $zeilen->where('runden', '>', 0);
        $runden = (int) $zeilen->sum('runden');
        $spende = (int) $zeilen->sum('spende');

        return [
            'anzahl' => $zeilen->count(),
            'teilnehmer' => $teilnehmer->count(),
            'runden' => $runden,
            'runden_schnitt' => $teilnehmer->count() ? round($runden / $teilnehmer->count(), 1) : 0,
            'runden_max' => (int) $zeilen->max('runden'),
            'km' => $this->event->km($runden),
            'spende' => $spende,
            'spende_schnitt' => $zeilen->count() ? (int) round($spende / $zeilen->count()) : 0,
            'spende_pro_runde' => $runden ? (int) round($spende / $runden) : 0,
            'sponsorings' => (int) $zeilen->sum('sponsorings'),
        ];
    }

    /**
     * Läufer gruppiert nach einer Dimension, inkl. Kennzahlen je Gruppe.
     *
     * @param  Collection<int, array<string, mixed>>|null  $zeilen
     * @return Collection<int, array<string, mixed>>
     */
    public function nachDimension(string $dimension, ?Collection $zeilen = null): Collection
    {
        $zeilen ??= $this->laeuferZeilen();

        [$key, $label, $sort] = match ($dimension) {
            'altersgruppe' => ['altersgruppe_key', 'altersgruppe', fn ($z) => $z['altersgruppe_sort']],
            'geschlecht' => ['geschlecht_key', 'geschlecht', fn ($z) => $z['geschlecht']],
            'kategorie' => ['kategorie_key', 'kategorie', fn ($z) => self::kategorieSortierung($z['kategorie_key'])],
            'klasse' => ['klasse', 'klasse', fn ($z) => sprintf('%03d-%s', $z['stufe'] ?? 999, $z['klasse'])],
            'stufe' => ['stufe', 'stufe', fn ($z) => $z['stufe'] ?? 999],
            'team' => ['team', 'team', fn ($z) => $z['team'] === '–' ? 'zzz' : mb_strtolower($z['team'])],
            'teilnahme' => ['teilnahme', 'teilnahme', fn ($z) => $z['teilnahme']],
            default => throw new \InvalidArgumentException("Unbekannte Dimension $dimension"),
        };

        return $zeilen
            ->groupBy(fn ($z) => (string) ($z[$key] ?? '–'))
            ->map(function (Collection $gruppe) use ($label, $sort, $dimension) {
                $erste = $gruppe->first();
                $bezeichnung = $erste[$label] ?? '–';
                if ($dimension === 'stufe') {
                    $bezeichnung = $erste['stufe'] ? 'Klasse '.$erste['stufe'] : 'ohne Klasse';
                }

                return ['label' => (string) $bezeichnung, 'sort' => $sort($erste), 'zeilen' => $gruppe] + $this->kennzahlen($gruppe);
            })
            ->sortBy('sort')
            ->values();
    }

    private static function kategorieSortierung(string $kategorie): int
    {
        $index = array_search($kategorie, array_column(Kategorie::cases(), 'value'), true);

        return $index === false ? 99 : $index;
    }

    /** @return array<string, int|float> */
    public function uebersicht(): array
    {
        $zeilen = $this->laeuferZeilen();
        $kennzahlen = $this->kennzahlen($zeilen);

        return $kennzahlen + [
            'brutto' => $this->bilanz->bruttoCent,
            'abgabe' => $this->bilanz->abgabeCent,
            'klassenkasse' => $this->bilanz->klassenkasseCent,
            'projekte' => $this->bilanz->projekteCent,
            'bezahlt' => $this->bilanz->bezahltCent,
            'offen' => $this->bilanz->offenCent(),
            'fest' => $this->bilanz->festCent,
            'rundenbetrag' => $this->bilanz->rundenCent,
            'sponsoren' => count($this->bilanz->sponsoren),
            'sponsorings_gesamt' => count($this->bilanz->sponsorings),
            'teams' => Teams::forEvent($this->event)->count(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function teams(): Collection
    {
        $zeilen = $this->laeuferZeilen()->whereNotNull('team_id')->groupBy('team_id');

        return Teams::forEvent($this->event)->with('besitzer')->get()
            ->map(function (Teams $team) use ($zeilen) {
                $mitglieder = $zeilen->get($team->id, collect());
                $k = $this->kennzahlen($mitglieder);
                $teamSpende = $this->bilanz->teams[$team->id]['spende'] ?? 0;
                $einzel = (int) $mitglieder->sum('einzel');

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'open' => $team->open,
                    'besitzer' => $team->besitzer?->name,
                    'mitglieder' => $k['anzahl'],
                    'teilnehmer' => $k['teilnehmer'],
                    'runden' => $k['runden'],
                    'runden_schnitt' => $k['anzahl'] ? round($k['runden'] / $k['anzahl'], 1) : 0,
                    'km' => $k['km'],
                    'team_spende' => $teamSpende,
                    'einzel_spende' => $einzel,
                    'gesamt' => $teamSpende + $einzel,
                    'sponsorings' => $this->bilanz->teams[$team->id]['sponsorings'] ?? 0,
                ];
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    public function klassen(): Collection
    {
        $zeilen = $this->laeuferZeilen()->whereNotNull('klasse_id')->where('kategorie_key', Kategorie::Schueler->value)->groupBy('klasse_id');
        $quoten = $this->bezahltQuoten();

        return Schulklasse::forEvent($this->event)->orderBy('stufe')->orderBy('name')->get()
            ->map(function (Schulklasse $klasse) use ($zeilen, $quoten) {
                $schueler = $zeilen->get($klasse->id, collect());
                $k = $this->kennzahlen($schueler);
                $werte = $this->bilanz->klassen[$klasse->id] ?? ['brutto' => 0, 'kasse' => 0];

                $bezahltBrutto = 0;
                foreach ($this->bilanz->klassenSponsoren[$klasse->id] ?? [] as $sponsorId => $anteil) {
                    $bezahltBrutto += (int) round($anteil * ($quoten[$sponsorId] ?? 0));
                }

                return [
                    'id' => $klasse->id,
                    'name' => $klasse->name,
                    'stufe' => $klasse->stufe,
                    'klassenleitung' => $klasse->klassenleitung,
                    'schueler' => $k['anzahl'],
                    'teilnehmer' => $k['teilnehmer'],
                    'runden' => $k['runden'],
                    'runden_schnitt' => $k['runden_schnitt'],
                    'km' => $k['km'],
                    'brutto' => $werte['brutto'],
                    'kasse' => $werte['kasse'],
                    'bezahlt_quote' => $werte['brutto'] > 0 ? round($bezahltBrutto / $werte['brutto'] * 100) : 0,
                    'kasse_bezahlt' => $werte['brutto'] > 0 ? (int) round($werte['kasse'] * $bezahltBrutto / $werte['brutto']) : 0,
                    'zeilen' => $schueler,
                ];
            });
    }

    /** Anteil (0..1) der bereits bezahlten Summe je Sponsor. */
    public function bezahltQuoten(): array
    {
        $quoten = [];
        foreach ($this->bilanz->sponsoren as $id => $s) {
            $quoten[$id] = $s['soll'] > 0 ? min(1, ($this->bilanz->zahlungen[$id] ?? 0) / $s['soll']) : 0;
        }

        return $quoten;
    }

    /** @return Collection<int, array<string, mixed>> */
    public function sponsoren(): Collection
    {
        $ids = array_keys($this->bilanz->sponsoren);

        return Sponsor::query()->whereIn('id', $ids)->get()
            ->map(fn (Sponsor $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'ort' => $s->ort,
                'sponsorings' => $this->bilanz->sponsoren[$s->id]['sponsorings'],
                'soll' => $this->bilanz->sponsorSoll($s->id),
                'ist' => $this->bilanz->sponsorIst($s->id),
            ])
            ->sortByDesc('soll')
            ->values();
    }

    /**
     * Verteilung der Spendenhöhen je Sponsor.
     *
     * @return list<array{label: string, anzahl: int, summe: int}>
     */
    public function spendenVerteilung(): array
    {
        $grenzen = [[0, 1000, 'unter 10 €'], [1000, 2500, '10–25 €'], [2500, 5000, '25–50 €'], [5000, 10000, '50–100 €'], [10000, 25000, '100–250 €'], [25000, PHP_INT_MAX, 'ab 250 €']];
        $verteilung = [];
        foreach ($grenzen as [$von, $bis, $label]) {
            $treffer = array_filter($this->bilanz->sponsoren, fn ($s) => $s['soll'] >= $von && $s['soll'] < $bis);
            $verteilung[] = ['label' => $label, 'anzahl' => count($treffer), 'summe' => array_sum(array_column($treffer, 'soll'))];
        }

        return $verteilung;
    }

    /** @return Collection<int, array{label: string, betrag: int, art: string}> */
    public function projekte(): Collection
    {
        $namen = Projects::query()->whereIn('id', array_keys($this->bilanz->projekte))->pluck('name', 'id');

        $zeilen = collect($this->bilanz->projekte)
            ->map(fn ($betrag, $id) => ['label' => $id === 0 ? 'ohne Projektzuordnung' : ($namen[$id] ?? "Projekt #$id"), 'betrag' => $betrag, 'art' => 'projekt'])
            ->sortByDesc('betrag')
            ->values();

        if ($this->bilanz->abgabeCent > 0) {
            $zeilen->push(['label' => 'Soziale Abgabe: '.$this->event->soziale_abgabe_empfaenger, 'betrag' => $this->bilanz->abgabeCent, 'art' => 'abgabe']);
        }
        if ($this->bilanz->klassenkasseCent > 0) {
            $zeilen->push(['label' => 'Klassenkassen', 'betrag' => $this->bilanz->klassenkasseCent, 'art' => 'klassen']);
        }

        return $zeilen;
    }

    /**
     * Rundenverteilung in Schritten von $breite Runden.
     *
     * @return list<array{label: string, anzahl: int}>
     */
    public function rundenHistogramm(int $breite = 5): array
    {
        $teilnehmer = $this->laeuferZeilen()->where('runden', '>', 0);
        $max = (int) $teilnehmer->max('runden');
        $buckets = [];
        for ($von = 1; $von <= max($max, 1); $von += $breite) {
            $bis = $von + $breite - 1;
            $buckets[] = ['label' => "{$von}–{$bis}", 'anzahl' => $teilnehmer->whereBetween('runden', [$von, $bis])->count()];
        }

        return $buckets;
    }

    /**
     * Die besten Läufer (nach Runden) je Altersgruppe und Geschlecht.
     *
     * @return Collection<int, array{label: string, zeilen: Collection}>
     */
    public function topJeAltersgruppe(int $anzahl = 3, bool $nachGeschlecht = false): Collection
    {
        return $this->laeuferZeilen()
            ->where('runden', '>', 0)
            ->groupBy(fn ($z) => $z['altersgruppe_key'].($nachGeschlecht ? '|'.$z['geschlecht_key'] : ''))
            ->map(fn (Collection $gruppe) => [
                'label' => $gruppe->first()['altersgruppe'].($nachGeschlecht ? ' · '.$gruppe->first()['geschlecht'] : ''),
                'sort' => $gruppe->first()['altersgruppe_sort'].($nachGeschlecht ? $gruppe->first()['geschlecht'] : ''),
                'zeilen' => $gruppe->sortByDesc('runden')->take($anzahl)->values(),
            ])
            ->sortBy('sort')
            ->values();
    }

    /**
     * Kernzahlen aller Veranstaltungen.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function jahresvergleich(): Collection
    {
        return Event::query()->orderBy('datum')->get()->map(function (Event $event) {
            $s = self::fuer($event);
            $u = $s->uebersicht();

            return [
                'id' => $event->id,
                'name' => $event->name,
                'datum' => $event->datum,
            ] + $u;
        });
    }
}
