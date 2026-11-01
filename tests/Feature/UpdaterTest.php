<?php

use App\Enums\UpdateZustand;
use App\Models\User;
use App\Services\Updater;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->verzeichnis = sys_get_temp_dir().DIRECTORY_SEPARATOR.'spendenlauf-updater-'.uniqid();
    config([
        'spendenlauf.updater.verzeichnis' => $this->verzeichnis,
        'spendenlauf.updater.branch' => 'main',
        'spendenlauf.updater.php' => 'php',
        'spendenlauf.updater.npm' => 'false',
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->verzeichnis);
});

/**
 * Simuliert git & Co.: $antworten ordnet Befehlsanfänge (z. B. "git rev-list --count HEAD..") einer Ausgabe
 * oder einem Exit-Code zu. Liefert die Liste aller ausgeführten Befehle.
 */
function prozesseFaelschen(array $antworten = []): ArrayObject
{
    $befehle = new ArrayObject;

    Process::fake(function (PendingProcess $prozess) use ($antworten, $befehle) {
        $befehl = is_array($prozess->command) ? implode(' ', $prozess->command) : $prozess->command;
        $befehle->append($befehl);

        foreach ($antworten as $anfang => $antwort) {
            if (str_starts_with($befehl, $anfang)) {
                return is_int($antwort) ? Process::result(errorOutput: 'Fehler bei '.$anfang, exitCode: $antwort) : Process::result($antwort);
            }
        }

        return Process::result();
    });

    return $befehle;
}

function updaterBenutzer(): User
{
    return User::factory()->create()->givePermissionTo(Permission::findOrCreate('system update', 'web'));
}

it('schützt die Update-Seite durch das Recht "system update"', function () {
    $befehle = prozesseFaelschen();

    $this->get(route('updates.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('updates.index'))->assertForbidden();
    $this->actingAs(updaterBenutzer())->get(route('updates.index'))->assertOk()->assertSee('System-Update');
});

it('ist abgeschaltet, wenn UPDATER_ENABLED=false', function () {
    config(['spendenlauf.updater.enabled' => false]);

    $this->actingAs(updaterBenutzer())->get(route('updates.index'))->assertNotFound();
});

it('zeigt neue Versionen nach der Prüfung an', function () {
    $befehle = prozesseFaelschen([
        'git rev-list --count HEAD..origin/main' => "2\n",
        'git rev-list --count origin/main..HEAD' => "0\n",
        'git log HEAD..origin/main' => "abc1234\x1f2026-11-01T10:00:00+01:00\x1fDreknor\x1fNeue Statistik\n"
            ."def5678\x1f2026-10-31T09:00:00+01:00\x1fDreknor\x1fFehler behoben\n",
    ]);

    $this->actingAs(updaterBenutzer())
        ->post(route('updates.pruefen'))
        ->assertRedirect(route('updates.index'))
        ->assertSessionHas('status', 'Es ist eine neue Version verfügbar (2 Änderung(en)).');

    expect($befehle->getArrayCopy())->toContain('git fetch --quiet origin main');

    $this->get(route('updates.index'))
        ->assertSee('Neue Statistik')
        ->assertSee('Fehler behoben')
        ->assertSee('Update jetzt installieren');
});

it('meldet Fehler beim Abruf verständlich', function () {
    $befehle = prozesseFaelschen(['git fetch' => 128]);

    $this->actingAs(updaterBenutzer())
        ->post(route('updates.pruefen'))
        ->assertSessionHas('type', 'danger');

    expect(app(Updater::class)->letztePruefung()['fehler'])->toContain('konnten nicht abgerufen werden');
});

it('startet das Update im Hintergrund', function () {
    $befehle = prozesseFaelschen();

    $this->actingAs($benutzer = updaterBenutzer())
        ->post(route('updates.starten'))
        ->assertRedirect(route('updates.index'));

    $updater = app(Updater::class);
    expect(collect($befehle->getArrayCopy())->contains(fn ($b) => str_contains($b, 'artisan') && str_contains($b, 'spendenlauf:update')))->toBeTrue()
        ->and($updater->laeuft())->toBeTrue()
        ->and($updater->status()['gestartet_von'])->toBe($benutzer->name);

    // kein zweites Update parallel
    $this->post(route('updates.starten'))->assertSessionHas('status', 'Es läuft bereits ein Update.');

    $this->getJson(route('updates.status'))->assertOk()->assertJson(['zustand' => 'gestartet']);
});

it('markiert ein nie angelaufenes Update als abgebrochen', function () {
    $befehle = prozesseFaelschen();
    app(Updater::class)->starten('Test');

    $this->travel(5)->minutes();

    expect(app(Updater::class)->status()['zustand'])->toBe('abgebrochen')
        ->and(app(Updater::class)->laeuft())->toBeFalse();
});

it('führt alle Schritte in der richtigen Reihenfolge aus', function () {
    $befehle = prozesseFaelschen([
        'git rev-list --count HEAD..origin/main' => "3\n",
        'git rev-list --count origin/main..HEAD' => "0\n",
        'git rev-parse --short HEAD' => "abc1234\n",
    ]);

    $this->artisan('spendenlauf:update')->assertSuccessful();

    $schritte = collect($befehle->getArrayCopy())->filter(fn ($b) => ! str_starts_with($b, 'git rev-') && ! str_starts_with($b, 'git status'))->values();
    expect($schritte->all())->toBe([
        'git fetch origin main',
        'php artisan down --retry=60',
        'git merge --ff-only origin/main',
        'composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader',
        'php artisan migrate --force',
        'php artisan optimize:clear',
        'php artisan queue:restart',
        'php artisan up',
    ]);

    $status = app(Updater::class)->status();
    expect($status['zustand'])->toBe(UpdateZustand::Erfolgreich->value)
        ->and(collect($status['schritte'])->pluck('zustand')->unique()->values()->all())->toBe(['ok', 'uebersprungen'])
        ->and($status['meldung'])->toContain('3 neue Commit(s)');
});

it('installiert ohne Dev-Pakete und baut Caches in der Produktion', function () {
    app()->detectEnvironment(fn () => 'production');
    $befehle = prozesseFaelschen(['git rev-list --count HEAD..origin/main' => "1\n"]);

    $this->artisan('spendenlauf:update')->assertSuccessful();

    expect($befehle->getArrayCopy())
        ->toContain('composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-dev')
        ->toContain('php artisan optimize');
});

it('baut das Frontend, wenn npm verfügbar ist', function () {
    config(['spendenlauf.updater.npm' => 'auto']);
    $befehle = prozesseFaelschen(['git rev-list --count HEAD..origin/main' => "1\n"]);

    $this->artisan('spendenlauf:update')->assertSuccessful();

    expect($befehle->getArrayCopy())->toContain('npm ci --no-audit --no-fund')->toContain('npm run build');
});

it('macht nichts, wenn die Version aktuell ist', function () {
    $befehle = prozesseFaelschen(['git rev-list --count' => "0\n"]);

    $this->artisan('spendenlauf:update')->assertSuccessful();

    expect(app(Updater::class)->status()['zustand'])->toBe('aktuell')
        ->and($befehle->getArrayCopy())->not->toContain('php artisan down --retry=60');
});

it('bricht bei geänderten Dateien auf dem Server vor dem Wartungsmodus ab', function () {
    $befehle = prozesseFaelschen(['git status --porcelain' => " M app/Models/User.php\n"]);

    $this->artisan('spendenlauf:update')->assertFailed();

    $status = app(Updater::class)->status();
    expect($status['zustand'])->toBe('fehlgeschlagen')
        ->and($status['meldung'])->toContain('app/Models/User.php')
        ->and($befehle->getArrayCopy())->not->toContain('php artisan down --retry=60');
});

it('stellt bei einem Fehler die vorherige Version wieder her und beendet den Wartungsmodus', function () {
    $befehle = prozesseFaelschen([
        'git rev-list --count HEAD..origin/main' => "1\n",
        'git rev-list --count origin/main..HEAD' => "0\n",
        'git rev-parse --short HEAD' => "abc1234\n",
        'php artisan migrate' => 1,
    ]);

    $this->artisan('spendenlauf:update')->assertFailed();

    expect($befehle->getArrayCopy())->toContain('git reset --hard abc1234')
        ->and(collect($befehle)->last())->toBe('php artisan up');

    $status = app(Updater::class)->status();
    expect($status['zustand'])->toBe('fehlgeschlagen')
        ->and($status['meldung'])->toContain('Datenbank aktualisieren')
        ->and($status['meldung'])->toContain('wiederhergestellt');
});

it('prüft per Befehl auf neue Versionen', function () {
    $befehle = prozesseFaelschen(['git rev-list --count HEAD..origin/main' => "4\n"]);

    $this->artisan('spendenlauf:update --check')
        ->expectsOutputToContain('4 neue Commit(s)')
        ->assertSuccessful();

    expect(app(Updater::class)->anzahlNeu())->toBe(4);
});
