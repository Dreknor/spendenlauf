<?php

namespace App\Services;

use App\Enums\UpdateZustand;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

/**
 * Online-Updater: prüft per git auf neue Versionen und spielt sie ein – mit denselben Schritten wie deploy.sh
 * (Wartungsmodus, git, Composer, Frontend-Build, Migrationen, Caches).
 *
 * Das Update selbst läuft als Artisan-Befehl im Hintergrund (spendenlauf:update), damit Webserver-Timeouts und
 * der Austausch der PHP-Dateien den Ablauf nicht abbrechen. Status und Protokoll liegen als Dateien in
 * storage/app/updater – nicht im Cache, denn "optimize:clear" leert den Cache mitten im Update.
 */
class Updater
{
    /** Gespeicherte Ausgabe im Status (das vollständige Protokoll steht in update.log). */
    private const AUSGABE_MAX = 30000;

    /** So lange darf ein gestartetes Update brauchen, bis der Hintergrundprozess die Sperre übernommen hat. */
    private const START_FRIST_SEKUNDEN = 120;

    private array $status = [];

    /** @var resource|null */
    private $sperre = null;

    /** @var (callable(string): void)|null */
    private $ausgabeAn = null;

    private float $zuletztGespeichert = 0;

    private bool $frontendBegonnen = false;

    public function aktiviert(): bool
    {
        return (bool) config('spendenlauf.updater.enabled');
    }

    /*
    |--------------------------------------------------------------------------
    | Versionen
    |--------------------------------------------------------------------------
    */

    /** Die installierte Version (aktueller Commit). */
    public function version(): array
    {
        $log = $this->git(['log', '-1', '--format=%H%x1f%h%x1f%cI%x1f%s']);

        if (! $log->successful() || trim($log->output()) === '') {
            return ['verfuegbar' => false, 'fehler' => $this->fehlertext($log)];
        }

        [$hash, $kurz, $datum, $betreff] = array_pad(explode("\x1f", trim($log->output()), 4), 4, '');
        $tag = $this->git(['describe', '--tags', '--always']);

        return [
            'verfuegbar' => true,
            'hash' => $hash,
            'kurz' => $kurz,
            'datum' => $datum,
            'betreff' => $betreff,
            'branch' => $this->branch(),
            'bezeichnung' => $tag->successful() && trim($tag->output()) !== '' ? trim($tag->output()) : $kurz,
        ];
    }

    /**
     * Holt den Stand des Repositories (git fetch) und vergleicht ihn mit der installierten Version.
     * Das Ergebnis wird gespeichert, damit die Verwaltung es ohne erneuten Abruf anzeigen kann.
     */
    public function pruefen(): array
    {
        $ergebnis = [
            'geprueft_am' => now()->toIso8601String(),
            'ziel' => $this->ziel(),
            'neu' => 0,
            'lokal_voraus' => 0,
            'commits' => [],
            'aenderungen' => [],
            'werkzeuge' => $this->werkzeuge(),
            'fehler' => null,
        ];

        $fetch = $this->git(['fetch', '--quiet', $this->remote(), $this->branch()], 120);

        if (! $fetch->successful()) {
            $ergebnis['fehler'] = 'Neue Versionen konnten nicht abgerufen werden: '.$this->fehlertext($fetch);

            return $this->speichern('pruefung', $ergebnis);
        }

        return $this->speichern('pruefung', $this->vergleichen($ergebnis));
    }

    /** Ergebnis der letzten Prüfung (ohne neuen Abruf). */
    public function letztePruefung(): ?array
    {
        return $this->lesen('pruefung');
    }

    /** Anzahl neuer Commits laut letzter Prüfung (für den Hinweis im Menü). */
    public function anzahlNeu(): int
    {
        return (int) ($this->letztePruefung()['neu'] ?? 0);
    }

    private function vergleichen(array $ergebnis): array
    {
        $ziel = $this->ziel();

        $ergebnis['neu'] = (int) trim($this->git(['rev-list', '--count', "HEAD..$ziel"])->output());
        $ergebnis['lokal_voraus'] = (int) trim($this->git(['rev-list', '--count', "$ziel..HEAD"])->output());
        $ergebnis['commits'] = $this->commits("HEAD..$ziel");
        $ergebnis['aenderungen'] = $this->lokaleAenderungen();

        return $ergebnis;
    }

    private function commits(string $bereich): array
    {
        $log = $this->git(['log', $bereich, '--max-count=50', '--format=%h%x1f%cI%x1f%an%x1f%s']);

        return collect(preg_split('/\R/', trim($log->output())))
            ->filter()
            ->map(function (string $zeile) {
                [$kurz, $datum, $autor, $betreff] = array_pad(explode("\x1f", $zeile, 4), 4, '');

                return compact('kurz', 'datum', 'autor', 'betreff');
            })
            ->values()
            ->all();
    }

    /** Geänderte Dateien, die git kennt (ein Update würde sie überschreiben oder scheitern). */
    private function lokaleAenderungen(): array
    {
        $status = $this->git(['status', '--porcelain', '--untracked-files=no']);

        return collect(preg_split('/\R/', rtrim($status->output())))
            ->map(fn (string $zeile) => trim(substr($zeile, 3)))
            ->filter()
            ->values()
            ->all();
    }

    /** Versionen der benötigten Programme – hilft bei der Fehlersuche auf dem Server. */
    private function werkzeuge(): array
    {
        $abfragen = [
            'PHP (CLI)' => [$this->php(), '-r', 'echo PHP_VERSION;'],
            'git' => ['git', '--version'],
            'Composer' => [...$this->composer(), '--version', '--no-ansi'],
        ];

        $npm = $this->npmEinstellung();
        if ($npm !== null) {
            $abfragen['npm'] = [$npm, '--version'];
        }

        $werkzeuge = [];
        foreach ($abfragen as $name => $befehl) {
            try {
                $ergebnis = $this->prozess(30)->run($befehl);
                $werkzeuge[$name] = [
                    'ok' => $ergebnis->successful(),
                    'version' => $ergebnis->successful() ? strtok(trim($ergebnis->output()), "\r\n") : $this->fehlertext($ergebnis),
                ];
            } catch (Throwable $e) {
                $werkzeuge[$name] = ['ok' => false, 'version' => $e->getMessage()];
            }
        }

        if ($npm === null) {
            $werkzeuge['npm'] = ['ok' => null, 'version' => 'deaktiviert (UPDATER_NPM=false)'];
        }

        return $werkzeuge;
    }

    /*
    |--------------------------------------------------------------------------
    | Update starten und überwachen
    |--------------------------------------------------------------------------
    */

    /**
     * Startet das Update als eigenständigen Hintergrundprozess und kehrt sofort zurück.
     *
     * @throws RuntimeException wenn bereits ein Update läuft
     */
    public function starten(string $ausgeloestVon, bool $erzwingen = false): void
    {
        if ($this->laeuft()) {
            throw new RuntimeException('Es läuft bereits ein Update.');
        }

        $this->status = $this->neuerStatus(UpdateZustand::Gestartet, $ausgeloestVon);
        $this->statusSpeichern(true);

        $befehl = [$this->php(), 'artisan', 'spendenlauf:update'];
        if ($erzwingen) {
            $befehl[] = '--force';
        }

        // Losgelöst starten, damit der Prozess das Ende der Web-Anfrage überlebt
        $befehl = implode(' ', array_map('escapeshellarg', $befehl));
        $this->prozess(30)->quietly()->run(PHP_OS_FAMILY === 'Windows'
            ? 'start "" /B '.$befehl.' > NUL 2>&1'
            : 'nohup '.$befehl.' > /dev/null 2>&1 &');
    }

    public function laeuft(): bool
    {
        return UpdateZustand::tryFrom($this->status()['zustand'] ?? '')?->aktiv() ?? false;
    }

    /**
     * Status des laufenden bzw. letzten Updates. Ein Update, dessen Prozess nicht mehr existiert
     * (z. B. vom Server beendet), wird als abgebrochen markiert.
     */
    public function status(): array
    {
        $status = $this->lesen('status') ?? [];
        $zustand = UpdateZustand::tryFrom($status['zustand'] ?? '');

        if ($zustand?->aktiv() && ! $this->sperreBelegt()) {
            $gestartet = Carbon::parse($status['gestartet_am'] ?? 'now');

            if ($zustand === UpdateZustand::Laeuft || $gestartet->diffInSeconds(now()) > self::START_FRIST_SEKUNDEN) {
                $status['zustand'] = UpdateZustand::Abgebrochen->value;
                $status['meldung'] = $zustand === UpdateZustand::Laeuft
                    ? 'Das Update wurde unerwartet beendet. Details stehen in storage/app/updater/update.log.'
                    : 'Das Update konnte nicht im Hintergrund gestartet werden. Bitte UPDATER_PHP prüfen oder "php artisan spendenlauf:update" auf dem Server ausführen.';
                $status['beendet_am'] = now()->toIso8601String();
                $this->speichern('status', $status);
            }
        }

        return $status;
    }

    /*
    |--------------------------------------------------------------------------
    | Update ausführen (Artisan-Befehl spendenlauf:update)
    |--------------------------------------------------------------------------
    */

    /**
     * @param  (callable(string): void)|null  $ausgabe  erhält die Ausgabe der einzelnen Schritte (Konsole)
     *
     * @throws RuntimeException wenn bereits ein Update läuft
     */
    public function ausfuehren(bool $erzwingen = false, ?callable $ausgabe = null): UpdateZustand
    {
        if (! $this->sperren()) {
            throw new RuntimeException('Es läuft bereits ein Update.');
        }

        $vorher = $this->lesen('status') ?? [];
        $ausgeloestVon = ($vorher['zustand'] ?? null) === UpdateZustand::Gestartet->value
            ? ($vorher['gestartet_von'] ?? 'Verwaltung')
            : 'Konsole';

        $this->ausgabeAn = $ausgabe;
        $this->status = $this->neuerStatus(UpdateZustand::Laeuft, $ausgeloestVon);
        $this->statusSpeichern(true);
        $this->protokoll(PHP_EOL.'==== Update gestartet am '.now()->format('d.m.Y H:i:s')." von {$ausgeloestVon} ====".PHP_EOL);

        try {
            $zustand = $this->ablauf($erzwingen);
        } catch (Throwable $e) {
            $zustand = UpdateZustand::Fehlgeschlagen;
            $this->status['meldung'] = $e->getMessage().($this->status['meldung'] ? ' '.$this->status['meldung'] : '');
            $this->protokoll(PHP_EOL.'FEHLER: '.$e->getMessage().PHP_EOL);
        }

        $this->protokoll(PHP_EOL.'==== Ende: '.$zustand->label().' ===='.PHP_EOL);
        $this->status['zustand'] = $zustand->value;
        $this->status['beendet_am'] = now()->toIso8601String();
        $this->statusSpeichern(true);

        // Anzeige "neue Version verfügbar" auf den neuen Stand bringen (ohne erneuten Abruf)
        if ($pruefung = $this->letztePruefung()) {
            $this->speichern('pruefung', $this->vergleichen($pruefung));
        }

        $this->entsperren();

        return $zustand;
    }

    private function ablauf(bool $erzwingen): UpdateZustand
    {
        $ziel = $this->ziel();
        $php = $this->php();

        $this->schritt('Voraussetzungen prüfen', function () {
            $repo = $this->git(['rev-parse', '--is-inside-work-tree']);
            if (! $repo->successful()) {
                throw new RuntimeException('Das git-Repository im Installationsverzeichnis konnte nicht gelesen werden: '.$this->fehlertext($repo));
            }

            if ($this->branch() === 'HEAD') {
                throw new RuntimeException('Es ist kein Branch ausgecheckt. Bitte UPDATER_BRANCH in der .env setzen.');
            }

            if ($aenderungen = $this->lokaleAenderungen()) {
                throw new RuntimeException('Auf dem Server wurden Dateien geändert ('.implode(', ', array_slice($aenderungen, 0, 5))
                    .(count($aenderungen) > 5 ? ', …' : '').'). Bitte die Änderungen zuerst committen oder verwerfen.');
            }
        });

        $this->befehl('Neue Version herunterladen', ['git', 'fetch', $this->remote(), $this->branch()], 300);

        $this->status['von'] = $this->kurzHash('HEAD');
        $neu = (int) trim($this->git(['rev-list', '--count', "HEAD..$ziel"])->output());
        $voraus = (int) trim($this->git(['rev-list', '--count', "$ziel..HEAD"])->output());

        if ($voraus > 0) {
            throw new RuntimeException("Die Installation enthält {$voraus} Commit(s), die in {$ziel} fehlen. Ein automatisches Update ist nicht möglich.");
        }

        if ($neu === 0 && ! $erzwingen) {
            $this->status['nach'] = $this->status['von'];
            $this->status['meldung'] = 'Die installierte Version ist aktuell.';

            return UpdateZustand::Aktuell;
        }

        $this->befehl('Wartungsmodus aktivieren', [$php, 'artisan', 'down', '--retry=60']);
        $codeGeaendert = false;
        $this->frontendBegonnen = false;

        try {
            $this->befehl('Neue Version übernehmen', ['git', 'merge', '--ff-only', $ziel]);
            $codeGeaendert = $neu > 0;
            $this->installieren();
            $this->befehl('Datenbank aktualisieren', [$php, 'artisan', 'migrate', '--force']);
            $this->cachesErneuern();
            $this->befehl('Queue-Worker neu starten', [$php, 'artisan', 'queue:restart']);
        } catch (Throwable $e) {
            if ($codeGeaendert) {
                $this->wiederherstellen();
            }

            throw $e;
        } finally {
            $this->befehl('Wartungsmodus beenden', [$php, 'artisan', 'up'], abbrechen: false);
        }

        $this->status['nach'] = $this->kurzHash('HEAD');
        $this->status['meldung'] = $neu > 0
            ? "Version {$this->status['nach']} wurde installiert ({$neu} neue Commit(s))."
            : 'Die Installationsschritte wurden erneut ausgeführt.';

        return UpdateZustand::Erfolgreich;
    }

    private function installieren(string $zusatz = '', bool $mitFrontend = true): void
    {
        $composer = [...$this->composer(), 'install', '--no-interaction', '--no-progress', '--prefer-dist', '--optimize-autoloader'];
        if (app()->isProduction()) {
            $composer[] = '--no-dev';
        }
        $this->befehl('PHP-Abhängigkeiten installieren'.$zusatz, $composer);

        if (! $mitFrontend) {
            return;
        }

        if ($npm = $this->npm()) {
            // Vite leert public/build schon zu Beginn – ab hier muss ein Rückbau das Frontend neu bauen
            $this->frontendBegonnen = true;
            $this->befehl('Frontend-Pakete installieren'.$zusatz, [$npm, 'ci', '--no-audit', '--no-fund']);
            $this->befehl('Frontend bauen'.$zusatz, [$npm, 'run', 'build']);
        } else {
            $this->uebersprungen('Frontend bauen'.$zusatz, 'npm ist nicht verfügbar – public/build muss separat aktualisiert werden.');
        }
    }

    private function cachesErneuern(string $zusatz = ''): void
    {
        $this->befehl('Caches leeren'.$zusatz, [$this->php(), 'artisan', 'optimize:clear']);

        if (app()->isProduction()) {
            $this->befehl('Caches aufbauen'.$zusatz, [$this->php(), 'artisan', 'optimize']);
        }
    }

    /** Nach einem Fehler den vorherigen Code wiederherstellen (Datenbank-Migrationen bleiben bestehen). */
    private function wiederherstellen(): void
    {
        if (! $this->status['von']) {
            $this->status['meldung'] = 'Die vorherige Version ist unbekannt und konnte nicht wiederhergestellt werden – bitte auf dem Server prüfen.';

            return;
        }

        try {
            $this->befehl('Vorherige Version wiederherstellen', ['git', 'reset', '--hard', $this->status['von']]);
            $this->installieren(' (vorherige Version)', mitFrontend: $this->frontendBegonnen);
            $this->cachesErneuern(' (vorherige Version)');
            $this->status['meldung'] = "Die vorherige Version {$this->status['von']} wurde wiederhergestellt.";
        } catch (Throwable) {
            $this->status['meldung'] = 'Die vorherige Version konnte nicht vollständig wiederhergestellt werden – bitte auf dem Server prüfen.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Schritte
    |--------------------------------------------------------------------------
    */

    private function schritt(string $name, callable $aktion): void
    {
        $index = $this->schrittBeginnen($name);

        try {
            $aktion();
        } catch (Throwable $e) {
            $this->schrittBeenden($index, 'fehler');

            throw $e;
        }

        $this->schrittBeenden($index, 'ok');
    }

    private function befehl(string $name, array $befehl, ?int $timeout = null, bool $abbrechen = true): void
    {
        $index = $this->schrittBeginnen($name);
        $this->protokoll(PHP_EOL.'$ '.implode(' ', $befehl).PHP_EOL);

        try {
            $ergebnis = $this->prozess($timeout ?? $this->timeout())
                ->run($befehl, fn (string $typ, string $text) => $this->ausgabe($text));
        } catch (Throwable $e) {
            $this->schrittBeenden($index, 'fehler');
            $this->protokoll($e->getMessage().PHP_EOL);

            if ($abbrechen) {
                throw new RuntimeException("Schritt „{$name}“ ist fehlgeschlagen: ".$e->getMessage(), previous: $e);
            }

            return;
        }

        if (! $ergebnis->successful()) {
            $this->schrittBeenden($index, 'fehler');

            if ($abbrechen) {
                throw new RuntimeException("Schritt „{$name}“ ist fehlgeschlagen: ".$this->fehlertext($ergebnis));
            }

            return;
        }

        $this->schrittBeenden($index, 'ok');
    }

    private function uebersprungen(string $name, string $hinweis): void
    {
        $this->status['schritte'][] = ['name' => $name, 'zustand' => 'uebersprungen', 'hinweis' => $hinweis, 'dauer' => null];
        $this->protokoll(PHP_EOL."{$name}: übersprungen – {$hinweis}".PHP_EOL);
        $this->statusSpeichern(true);
    }

    private function schrittBeginnen(string $name): int
    {
        $this->status['schritte'][] = ['name' => $name, 'zustand' => 'laeuft', 'hinweis' => null, 'dauer' => null, 'start' => microtime(true)];
        $this->statusSpeichern(true);

        return array_key_last($this->status['schritte']);
    }

    private function schrittBeenden(int $index, string $zustand): void
    {
        $schritt = &$this->status['schritte'][$index];
        $schritt['zustand'] = $zustand;
        $schritt['dauer'] = round(microtime(true) - $schritt['start'], 1);
        unset($schritt['start']);

        $this->statusSpeichern(true);
    }

    private function ausgabe(string $text): void
    {
        $this->protokoll($text);
        $this->statusSpeichern();
    }

    /** Schreibt ins Protokoll (update.log), in die Live-Ausgabe der Verwaltung und ggf. auf die Konsole. */
    private function protokoll(string $text): void
    {
        File::ensureDirectoryExists($this->verzeichnis());
        File::append($this->pfad('update.log'), $text);

        $this->status['ausgabe'] = mb_substr(($this->status['ausgabe'] ?? '').$text, -self::AUSGABE_MAX);

        if ($this->ausgabeAn) {
            ($this->ausgabeAn)($text);
        }
    }

    private function neuerStatus(UpdateZustand $zustand, string $ausgeloestVon): array
    {
        return [
            'zustand' => $zustand->value,
            'gestartet_am' => now()->toIso8601String(),
            'gestartet_von' => $ausgeloestVon,
            'beendet_am' => null,
            'von' => null,
            'nach' => null,
            'meldung' => null,
            'schritte' => [],
            'ausgabe' => '',
        ];
    }

    private function statusSpeichern(bool $sofort = false): void
    {
        // Ausgaben kommen in vielen kleinen Stücken – höchstens einmal pro Sekunde schreiben
        if (! $sofort && microtime(true) - $this->zuletztGespeichert < 1) {
            return;
        }

        $this->speichern('status', $this->status);
        $this->zuletztGespeichert = microtime(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Sperre (verhindert parallele Updates; überlebt "optimize:clear")
    |--------------------------------------------------------------------------
    */

    private function sperren(): bool
    {
        File::ensureDirectoryExists($this->verzeichnis());
        $this->sperre = fopen($this->pfad('update.lock'), 'c');

        if ($this->sperre && flock($this->sperre, LOCK_EX | LOCK_NB)) {
            return true;
        }

        $this->sperre = null;

        return false;
    }

    private function entsperren(): void
    {
        if ($this->sperre) {
            flock($this->sperre, LOCK_UN);
            fclose($this->sperre);
            $this->sperre = null;
        }
    }

    private function sperreBelegt(): bool
    {
        if ($this->sperre) {
            return true;
        }

        if (! is_file($this->pfad('update.lock'))) {
            return false;
        }

        $datei = fopen($this->pfad('update.lock'), 'c');
        if (! $datei) {
            return false;
        }

        $frei = flock($datei, LOCK_EX | LOCK_NB);
        if ($frei) {
            flock($datei, LOCK_UN);
        }
        fclose($datei);

        return ! $frei;
    }

    /*
    |--------------------------------------------------------------------------
    | Hilfsfunktionen
    |--------------------------------------------------------------------------
    */

    private function git(array $argumente, int $timeout = 60): ProcessResult
    {
        try {
            return $this->prozess($timeout)->run(['git', ...$argumente]);
        } catch (Throwable $e) {
            return Process::result(errorOutput: $e->getMessage(), exitCode: 1);
        }
    }

    private function prozess(?int $timeout = null)
    {
        return Process::path(base_path())
            ->env($this->umgebung())
            ->timeout($timeout ?? 60);
    }

    private function umgebung(): array
    {
        // Symfony Process vererbt im Web-Kontext nur Variablen, die auch in $_SERVER stehen –
        // die für git, Composer und npm wichtigen daher ausdrücklich weitergeben
        $umgebung = collect(['PATH', 'HOME', 'USERPROFILE', 'APPDATA', 'LOCALAPPDATA', 'SYSTEMROOT', 'TEMP', 'TMP', 'TMPDIR', 'COMPOSER_HOME'])
            ->mapWithKeys(fn (string $name) => [$name => getenv($name)])
            ->filter(fn ($wert) => is_string($wert) && $wert !== '')
            ->all();

        $umgebung['GIT_TERMINAL_PROMPT'] = '0';
        $umgebung['COMPOSER_NO_INTERACTION'] = '1';

        // Unter PHP-FPM fehlen oft PATH und HOME
        if (! isset($umgebung['PATH']) && PHP_OS_FAMILY !== 'Windows') {
            $umgebung['PATH'] = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
        }

        // Ohne TEMP fällt PHP unter Windows auf C:\Windows zurück – dort darf der Prozess nichts anlegen
        if (PHP_OS_FAMILY === 'Windows' && ! isset($umgebung['TEMP']) && ! isset($umgebung['TMP'])) {
            File::ensureDirectoryExists($tmp = $this->pfad('tmp'));
            $umgebung['TEMP'] = $umgebung['TMP'] = $tmp;
        }

        if (! isset($umgebung['HOME']) && ! isset($umgebung['COMPOSER_HOME']) && ! isset($umgebung['APPDATA'])) {
            $umgebung['COMPOSER_HOME'] = $this->pfad('composer');
        }

        return $umgebung;
    }

    private function remote(): string
    {
        return (string) config('spendenlauf.updater.remote', 'origin');
    }

    private function branch(): string
    {
        if ($branch = config('spendenlauf.updater.branch')) {
            return $branch;
        }

        $ergebnis = $this->git(['rev-parse', '--abbrev-ref', 'HEAD']);

        return $ergebnis->successful() && trim($ergebnis->output()) !== '' ? trim($ergebnis->output()) : 'HEAD';
    }

    private function ziel(): string
    {
        return $this->remote().'/'.$this->branch();
    }

    private function kurzHash(string $ref): ?string
    {
        $ergebnis = $this->git(['rev-parse', '--short', $ref]);

        return $ergebnis->successful() ? (trim($ergebnis->output()) ?: null) : null;
    }

    private function php(): string
    {
        return config('spendenlauf.updater.php')
            ?: (new PhpExecutableFinder)->find(false)
            ?: 'php';
    }

    private function composer(): array
    {
        return preg_split('/\s+/', trim((string) config('spendenlauf.updater.composer', 'composer')) ?: 'composer');
    }

    /** npm-Befehl laut Einstellung oder null, wenn das Frontend nicht gebaut werden soll. */
    private function npmEinstellung(): ?string
    {
        $wert = config('spendenlauf.updater.npm', 'auto');

        if ($wert === false || $wert === null || in_array(strtolower((string) $wert), ['', '0', 'false', 'off', 'no', 'nein'], true)) {
            return null;
        }

        return $wert === true || in_array(strtolower((string) $wert), ['1', 'true', 'auto'], true) ? 'npm' : (string) $wert;
    }

    /** npm-Befehl, wenn das Frontend gebaut werden soll und npm verfügbar ist. */
    private function npm(): ?string
    {
        $npm = $this->npmEinstellung();

        if ($npm === null) {
            return null;
        }

        try {
            return $this->prozess(30)->run([$npm, '--version'])->successful() ? $npm : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function timeout(): int
    {
        return max(60, (int) config('spendenlauf.updater.timeout', 900));
    }

    private function fehlertext(ProcessResult $ergebnis): string
    {
        $text = trim($ergebnis->errorOutput()) ?: trim($ergebnis->output()) ?: 'Exit-Code '.$ergebnis->exitCode();

        if (str_contains($text, 'dubious ownership')) {
            return 'git vertraut dem Installationsverzeichnis nicht (anderer Besitzer). Abhilfe auf dem Server: '
                .'git config --global --add safe.directory '.base_path();
        }

        return mb_strlen($text) > 600 ? '…'.mb_substr($text, -600) : $text;
    }

    private function verzeichnis(): string
    {
        return config('spendenlauf.updater.verzeichnis') ?: storage_path('app/updater');
    }

    private function pfad(string $datei): string
    {
        return $this->verzeichnis().DIRECTORY_SEPARATOR.$datei;
    }

    private function lesen(string $name): ?array
    {
        $pfad = $this->pfad("$name.json");

        if (! is_file($pfad)) {
            return null;
        }

        $daten = json_decode((string) @file_get_contents($pfad), true);

        return is_array($daten) ? $daten : null;
    }

    private function speichern(string $name, array $daten): array
    {
        File::ensureDirectoryExists($this->verzeichnis());
        $json = json_encode($daten, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        try {
            // Atomar ersetzen, damit die Statusabfrage nie eine halb geschriebene Datei liest
            File::replace($this->pfad("$name.json"), $json);
        } catch (Throwable) {
            File::put($this->pfad("$name.json"), $json, true);
        }

        return $daten;
    }
}
