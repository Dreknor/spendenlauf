<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Laeufer;
use App\Services\SpendenRechner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Importiert Runden aus einer Datei (CSV/Excel) oder von der Ergebnisseite der Zeitmessung.
 *
 * Erkannt werden Kopfzeilen mit einer Spalte „Startnummer“/„Startnr“/„StNr“ und einer Spalte „Runden“.
 * Läufer, deren Runden manuell eingegeben wurden, werden nicht überschrieben (außer $manuelleUeberschreiben).
 */
class ImportRunden
{
    public function __construct(private readonly Event $event, private readonly bool $manuelleUeberschreiben = false) {}

    /**
     * @return array{aktualisiert: int, unveraendert: int, uebersprungen: list<int>, unbekannt: list<int>, zeilen: int}
     */
    public function ausDatei(string $pfad, ?string $originalName = null): array
    {
        $endung = strtolower(pathinfo($originalName ?? $pfad, PATHINFO_EXTENSION));

        $zeilen = in_array($endung, ['csv', 'txt', ''], true)
            ? $this->csvLesen($pfad)
            : Excel::toArray(new class {}, $pfad)[0] ?? [];

        return $this->uebernehmen($this->datensaetze($zeilen));
    }

    /**
     * Lädt die aktuelle CSV-Datei von der Ergebnisseite der Zeitmessung (z. B. berlin-timing.de).
     */
    public function vonUrl(?string $url = null): array
    {
        $url ??= $this->event->import_url;
        if (blank($url)) {
            throw new RuntimeException('Für die Veranstaltung ist keine Import-URL hinterlegt.');
        }

        $antwort = Http::timeout(20)->get($url)->throw();
        $inhalt = $antwort->body();

        // Direkter CSV-Link oder Ergebnisseite mit Link auf „Liste/xyz.csv“
        if (! str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?? ''), '.csv')) {
            if (! preg_match('~(?:href=["\']?)?([^"\'\s>]*Liste/[A-Za-z0-9_\-]+\.csv)~i', $inhalt, $treffer)) {
                throw new RuntimeException('Auf der Ergebnisseite wurde keine CSV-Datei gefunden.');
            }
            $csvUrl = $this->absoluteUrl($treffer[1], $url);
            $inhalt = Http::timeout(20)->get($csvUrl)->throw()->body();
        }

        $datei = 'imports/runden-'.now()->format('Ymd-His').'.csv';
        Storage::disk('local')->put($datei, $inhalt);

        return $this->ausDatei(Storage::disk('local')->path($datei), $datei);
    }

    /**
     * @param  list<array{startnummer: int, runden: int}>  $datensaetze
     */
    public function uebernehmen(array $datensaetze): array
    {
        $ergebnis = ['aktualisiert' => 0, 'unveraendert' => 0, 'uebersprungen' => [], 'unbekannt' => [], 'zeilen' => count($datensaetze)];
        DB::transaction(function () use ($datensaetze, &$ergebnis) {
            // Innerhalb der Transaktion gesperrt laden: eine gleichzeitige manuelle Eingabe darf nicht
            // mit einem veralteten Stand überschrieben werden
            $laeufer = Laeufer::forEvent($this->event)->whereNotNull('startnummer')->lockForUpdate()->get()->keyBy('startnummer');

            foreach ($datensaetze as $satz) {
                $l = $laeufer->get($satz['startnummer']);

                if (! $l) {
                    $ergebnis['unbekannt'][] = $satz['startnummer'];

                    continue;
                }
                if ($l->runden_manuell && ! $this->manuelleUeberschreiben) {
                    if ($l->runden !== $satz['runden']) {
                        $ergebnis['uebersprungen'][] = $satz['startnummer'];
                    }

                    continue;
                }
                if ($l->runden === $satz['runden']) {
                    $ergebnis['unveraendert']++;

                    continue;
                }

                $l->runden = $satz['runden'];
                $l->runden_manuell = false;
                $l->runden_aktualisiert_am = now();
                $l->saveQuietly();
                $ergebnis['aktualisiert']++;
            }
        });

        SpendenRechner::vergessen();
        Log::info('Runden-Import', $ergebnis + ['event' => $this->event->id]);

        return $ergebnis;
    }

    /**
     * Sucht die Kopfzeile und liefert Startnummer/Runden-Paare.
     *
     * @param  array<int, array<int, mixed>>  $zeilen
     * @return list<array{startnummer: int, runden: int}>
     */
    public function datensaetze(array $zeilen): array
    {
        $spalteNr = $spalteRunden = null;
        $daten = [];

        foreach ($zeilen as $zeile) {
            $zeile = array_values($zeile);

            if ($spalteNr === null) {
                foreach ($zeile as $i => $zelle) {
                    $name = strtolower(preg_replace('/[^a-z]/i', '', (string) $zelle));
                    if (in_array($name, ['startnummer', 'startnr', 'stnr', 'nr', 'startno', 'bib'], true)) {
                        $spalteNr ??= $i;
                    } elseif (in_array($name, ['runden', 'rd', 'laps', 'anzahlrunden'], true)) {
                        $spalteRunden ??= $i;
                    }
                }
                if ($spalteNr === null || $spalteRunden === null) {
                    $spalteNr = $spalteRunden = null;
                }

                continue;
            }

            $nr = trim((string) ($zeile[$spalteNr] ?? ''));
            if (! ctype_digit($nr)) {
                continue;
            }
            $runden = (int) preg_replace('/\D/', '', (string) ($zeile[$spalteRunden] ?? '0'));
            $daten[(int) $nr] = ['startnummer' => (int) $nr, 'runden' => $runden];
        }

        if ($spalteNr === null) {
            throw new RuntimeException('Keine Spalten „Startnummer“ und „Runden“ gefunden.');
        }

        return array_values($daten);
    }

    /** @return list<list<string>> */
    private function csvLesen(string $pfad): array
    {
        $inhalt = file_get_contents($pfad);
        if ($inhalt === false) {
            throw new RuntimeException('Die Datei konnte nicht gelesen werden.');
        }
        if (! mb_check_encoding($inhalt, 'UTF-8')) {
            $inhalt = mb_convert_encoding($inhalt, 'UTF-8', 'Windows-1252');
        }
        $inhalt = preg_replace('/^\xEF\xBB\xBF/', '', $inhalt);

        $ersteZeilen = implode("\n", array_slice(preg_split('/\R/', $inhalt), 0, 10));
        $trenner = substr_count($ersteZeilen, ';') >= substr_count($ersteZeilen, ',') ? ';' : ',';
        if (substr_count($ersteZeilen, "\t") > substr_count($ersteZeilen, $trenner)) {
            $trenner = "\t";
        }

        return array_map(fn ($z) => str_getcsv($z, $trenner, '"', ''), preg_split('/\R/', trim($inhalt)));
    }

    private function absoluteUrl(string $link, string $basis): string
    {
        if (preg_match('~^https?://~i', $link)) {
            return $link;
        }
        $teile = parse_url($basis);
        $wurzel = $teile['scheme'].'://'.$teile['host'].(isset($teile['port']) ? ':'.$teile['port'] : '');

        if (str_starts_with($link, '/')) {
            return $wurzel.$link;
        }

        // berlin-timing verlinkt „Liste/…“ relativ zur Domain
        return $wurzel.'/'.ltrim($link, './');
    }
}
