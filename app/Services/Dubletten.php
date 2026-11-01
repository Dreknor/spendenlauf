<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Support\Namen;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Erkennt doppelt angelegte Läufer und Sponsoren.
 *
 * „sicher“: gleicher (normalisierter) Name und gleiches Geburtsdatum, bei Sponsoren gleiche E-Mail
 * oder gleicher Name mit gleicher Anschrift.
 * „ähnlich“: Abkürzung, Umlaut-Schreibweise oder kleiner Tippfehler – muss bestätigt werden.
 */
class Dubletten
{
    public const SICHER = 'sicher';

    public const AEHNLICH = 'aehnlich';

    // ---------------------------------------------------------------------
    // Läufer
    // ---------------------------------------------------------------------

    /**
     * Mögliche Dubletten eines neuen Läufers im Event.
     *
     * @return Collection<int, array{laeufer: Laeufer, grad: string}>
     */
    public static function laeuferKandidaten(Event $event, string $vorname, string $nachname, mixed $geburtsdatum = null, ?int $ausser = null): Collection
    {
        $datum = self::datum($geburtsdatum);
        $vor = Namen::normalisiere($vorname);
        $nach = Namen::normalisiere($nachname);

        return Laeufer::forEvent($event)
            ->with('besitzer')
            ->when($ausser, fn ($q) => $q->whereKeyNot($ausser))
            ->get()
            ->map(fn (Laeufer $l) => ['laeufer' => $l, 'grad' => self::laeuferGrad($vor, $nach, $datum, $l)])
            ->filter(fn ($k) => $k['grad'] !== null)
            ->sortBy(fn ($k) => $k['grad'] === self::SICHER ? 0 : 1)
            ->values();
    }

    private static function laeuferGrad(string $vor, string $nach, ?string $datum, Laeufer $l): ?string
    {
        $lVor = Namen::normalisiere($l->vorname);
        $lNach = Namen::normalisiere($l->nachname);
        $lDatum = $l->geburtsdatum?->format('Y-m-d');
        $datumGleich = $datum !== null && $lDatum !== null && $datum === $lDatum;
        $datumOffen = $datum === null || $lDatum === null;

        if ($vor === $lVor && $nach === $lNach && ($datumGleich || $datumOffen)) {
            return self::SICHER;
        }

        // Vor- und Nachname vertauscht
        if ($vor === $lNach && $nach === $lVor && ($datumGleich || $datumOffen)) {
            return self::AEHNLICH;
        }

        $namenAehnlich = Namen::aehnlich($vor, $lVor) && Namen::aehnlich($nach, $lNach, 1);

        if ($namenAehnlich && ($datumGleich || $datumOffen)) {
            return self::AEHNLICH;
        }

        // gleicher Name, Geburtsdatum mit Zahlendreher/Tippfehler
        if ($vor === $lVor && $nach === $lNach && $datum && $lDatum && levenshtein($datum, $lDatum) <= 1) {
            return self::AEHNLICH;
        }

        return null;
    }

    /**
     * Alle möglichen Läufer-Dubletten eines Events (für die Bereinigung durch das Orga-Team).
     *
     * @return Collection<int, array{a: Laeufer, b: Laeufer, grad: string}>
     */
    public static function laeuferPaare(Event $event): Collection
    {
        $laeufer = Laeufer::forEvent($event)->with(['besitzer', 'schulklasse', 'team'])->withCount('sponsorings')->orderBy('id')->get()->values();
        $ignoriert = self::ignoriert('laeufer');
        $paare = collect();

        for ($i = 0; $i < $laeufer->count(); $i++) {
            $a = $laeufer[$i];
            $vor = Namen::normalisiere($a->vorname);
            $nach = Namen::normalisiere($a->nachname);
            for ($j = $i + 1; $j < $laeufer->count(); $j++) {
                $b = $laeufer[$j];
                if (isset($ignoriert[$a->id.'-'.$b->id])) {
                    continue;
                }
                if ($grad = self::laeuferGrad($vor, $nach, $a->geburtsdatum?->format('Y-m-d'), $b)) {
                    $paare->push(['a' => $a, 'b' => $b, 'grad' => $grad]);
                }
            }
        }

        return $paare->sortBy(fn ($p) => $p['grad'] === self::SICHER ? 0 : 1)->values();
    }

    // ---------------------------------------------------------------------
    // Sponsoren
    // ---------------------------------------------------------------------

    /**
     * Sucht einen bereits vorhandenen Sponsor: zuerst über die E-Mail, dann über Name/Firma + Anschrift.
     * Gleicher Name in derselben PLZ reicht nicht – zwei „Anna Schmidt“ im selben Ort sind verschiedene Personen.
     */
    public static function vorhandenerSponsor(array $daten): ?Sponsor
    {
        if (filled($daten['email'] ?? null)) {
            $sponsor = Sponsor::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($daten['email']))])->first();
            if ($sponsor) {
                return $sponsor;
            }
        }

        return self::sponsorKandidaten($daten)->first(fn ($k) => $k['grad'] === self::SICHER)['sponsor'] ?? null;
    }

    /**
     * @return Collection<int, array{sponsor: Sponsor, grad: string}>
     */
    public static function sponsorKandidaten(array $daten, ?int $ausser = null): Collection
    {
        $schluessel = self::sponsorSchluessel($daten);
        if ($schluessel['name'] === '') {
            return collect();
        }

        return Sponsor::query()
            ->when($ausser, fn ($q) => $q->whereKeyNot($ausser))
            ->when(filled($daten['plz'] ?? null), fn ($q) => $q->where('plz', trim($daten['plz'])))
            ->get()
            ->map(fn (Sponsor $s) => ['sponsor' => $s, 'grad' => self::sponsorGrad($schluessel, $s)])
            ->filter(fn ($k) => $k['grad'] !== null)
            ->values();
    }

    /**
     * @return Collection<int, array{a: Sponsor, b: Sponsor, grad: string}>
     */
    public static function sponsorPaare(): Collection
    {
        $ignoriert = self::ignoriert('sponsor');
        $paare = collect();

        // Nur innerhalb gleicher PLZ vergleichen (bzw. gleicher E-Mail)
        $gruppen = Sponsor::query()->withCount(['sponsorings' => fn ($q) => $q->withoutGlobalScope('event')])->orderBy('id')->get()->groupBy(fn (Sponsor $s) => trim((string) $s->plz));

        foreach ($gruppen as $gruppe) {
            $gruppe = $gruppe->values();
            for ($i = 0; $i < $gruppe->count(); $i++) {
                $schluessel = self::sponsorSchluessel($gruppe[$i]->only(self::SPONSOR_FELDER));
                for ($j = $i + 1; $j < $gruppe->count(); $j++) {
                    $a = $gruppe[$i];
                    $b = $gruppe[$j];
                    if (! isset($ignoriert[$a->id.'-'.$b->id]) && ($grad = self::sponsorGrad($schluessel, $b))) {
                        $paare->push(['a' => $a, 'b' => $b, 'grad' => $grad]);
                    }
                }
            }
        }

        return $paare->sortBy(fn ($p) => $p['grad'] === self::SICHER ? 0 : 1)->values();
    }

    private const SPONSOR_FELDER = ['vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz'];

    /** @return array{name: string, vor: string, nach: string, firma: string, email: string, anschrift: string} */
    private static function sponsorSchluessel(array $daten): array
    {
        $vor = Namen::normalisiere($daten['vorname'] ?? '');
        $nach = Namen::normalisiere($daten['nachname'] ?? '');
        $firma = Namen::normalisiere($daten['firmenname'] ?? '');
        $strasse = Namen::strasse($daten['strasse'] ?? '');

        return [
            'name' => $vor.$nach ?: $firma,
            'vor' => $vor,
            'nach' => $nach,
            'firma' => $firma,
            'email' => mb_strtolower(trim((string) ($daten['email'] ?? ''))),
            'anschrift' => $strasse !== '' ? trim((string) ($daten['plz'] ?? '')).'|'.$strasse : '',
        ];
    }

    private static function sponsorGrad(array $neu, Sponsor $s): ?string
    {
        $alt = self::sponsorSchluessel($s->only(self::SPONSOR_FELDER));

        if ($neu['email'] !== '' && $neu['email'] === $alt['email']) {
            return self::SICHER;
        }

        // Zwei verschiedene, bekannte E-Mail-Adressen sprechen für verschiedene Personen
        $emailWiderspruch = $neu['email'] !== '' && $alt['email'] !== '';
        $anschriftGleich = $neu['anschrift'] !== '' && $neu['anschrift'] === $alt['anschrift'];

        // Gleicher Name ist nur mit gleicher Anschrift sicher dieselbe Person – sonst nachfragen
        if ($neu['name'] === $alt['name'] && $neu['firma'] === $alt['firma']) {
            return $anschriftGleich && ! $emailWiderspruch ? self::SICHER : self::AEHNLICH;
        }

        $firmaPasst = ($neu['firma'] === '' && $alt['firma'] === '') || Namen::aehnlich($neu['firma'], $alt['firma']);
        $personPasst = ($neu['vor'].$neu['nach'] === '' && $alt['vor'].$alt['nach'] === '')
            || (Namen::aehnlich($neu['vor'], $alt['vor']) && Namen::aehnlich($neu['nach'], $alt['nach'], 1));

        return $firmaPasst && $personPasst ? self::AEHNLICH : null;
    }

    // ---------------------------------------------------------------------

    public static function ignorieren(string $typ, int $a, int $b): void
    {
        DB::table('dubletten_ignoriert')->insertOrIgnore([
            'typ' => $typ, 'id_a' => min($a, $b), 'id_b' => max($a, $b), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, true> */
    private static function ignoriert(string $typ): array
    {
        return DB::table('dubletten_ignoriert')->where('typ', $typ)->get(['id_a', 'id_b'])
            ->mapWithKeys(fn ($r) => [$r->id_a.'-'.$r->id_b => true])->all();
    }

    private static function datum(mixed $wert): ?string
    {
        if ($wert instanceof CarbonInterface) {
            return $wert->format('Y-m-d');
        }
        if (blank($wert)) {
            return null;
        }

        try {
            return Carbon::parse($wert)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
