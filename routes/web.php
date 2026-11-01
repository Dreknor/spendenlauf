<?php

use App\Http\Controllers\AbrechnungController;
use App\Http\Controllers\AuswertungController;
use App\Http\Controllers\DublettenController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LaeuferController;
use App\Http\Controllers\MitverwaltungController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\PublicSponsorController;
use App\Http\Controllers\RundenController;
use App\Http\Controllers\SchnellerfassungController;
use App\Http\Controllers\SchulklasseController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\SponsoringController;
use App\Http\Controllers\SponsorPortalController;
use App\Http\Controllers\StartnummerController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SucheController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZahlungController;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

/*
|--------------------------------------------------------------------------
| Öffentlich
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('welcome');
Route::get('projekte', [ProjectsController::class, 'index'])->name('projects.index');
Route::redirect('projects', 'projekte');
Route::get('stats/{full?}', StatsController::class)->name('stats');
Route::get('image/{media}', ImageController::class)->name('image');

// Sponsor-Link eines Läufers (wird von Eltern/Läufern geteilt)
Route::get('laeufer/public/{uuid}', [PublicSponsorController::class, 'show'])->name('public.sponsor.show');
Route::post('laeufer/public/{uuid}', [PublicSponsorController::class, 'store'])
    ->middleware(['throttle:10,1', ProtectAgainstSpam::class])
    ->name('public.sponsor.store');
// Bestätigungslink aus der Mail (signiert, zeitlich begrenzt)
Route::get('spende/bestaetigen/{zusage}', [PublicSponsorController::class, 'bestaetigung'])->whereNumber('zusage')->name('public.sponsor.bestaetigung');
Route::post('spende/bestaetigen/{zusage}', [PublicSponsorController::class, 'bestaetigen'])->whereNumber('zusage')->middleware('throttle:10,1')->name('public.sponsor.bestaetigen');

// Persönliche Seite eines Sponsors (Link aus der Zahlungsaufforderung)
Route::get('sponsor/{uuid}', [SponsorPortalController::class, 'show'])->name('public.sponsor.konto');
Route::get('sponsor/{uuid}/nachweis', [SponsorPortalController::class, 'nachweis'])->name('public.sponsor.nachweis');

/*
|--------------------------------------------------------------------------
| Angemeldete Benutzer
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('home', HomeController::class)->name('home');
    Route::view('profil', 'auth.profil')->name('profil');

    Route::post('laeufer/uebernehmen', [LaeuferController::class, 'uebernehmen'])->name('laeufer.uebernehmen');
    Route::resource('laeufer', LaeuferController::class)->parameters(['laeufer' => 'laeufer']);
    Route::post('laeufer/{laeufer}/team', [LaeuferController::class, 'teamBeitreten'])->name('laeufer.team.join');
    Route::delete('laeufer/{laeufer}/team', [LaeuferController::class, 'teamVerlassen'])->name('laeufer.team.leave');
    Route::get('laeufer/{laeufer}/bescheinigung', [LaeuferController::class, 'bescheinigung'])->name('laeufer.bescheinigung');

    // Mitverwaltung (z. B. zweiter Elternteil)
    Route::post('laeufer/{laeufer}/mitverwaltung', [MitverwaltungController::class, 'anfragen'])->middleware('throttle:10,1')->name('mitverwaltung.anfragen');
    Route::post('laeufer/{laeufer}/mitverwalter', [MitverwaltungController::class, 'hinzufuegen'])->middleware('throttle:10,1')->name('mitverwaltung.hinzufuegen');
    Route::delete('laeufer/{laeufer}/mitverwalter/{user}', [MitverwaltungController::class, 'entfernen'])->name('mitverwaltung.entfernen');
    Route::post('mitverwaltung/{anfrage}/annehmen', [MitverwaltungController::class, 'annehmen'])->name('mitverwaltung.annehmen');
    Route::post('mitverwaltung/{anfrage}/ablehnen', [MitverwaltungController::class, 'ablehnen'])->name('mitverwaltung.ablehnen');
    Route::post('mitverwaltung/einladung/{einladung}/annehmen', [MitverwaltungController::class, 'einladungAnnehmen'])->name('mitverwaltung.einladung.annehmen');
    Route::post('mitverwaltung/einladung/{einladung}/ablehnen', [MitverwaltungController::class, 'einladungAblehnen'])->name('mitverwaltung.einladung.ablehnen');
    Route::delete('mitverwaltung/einladung/{einladung}', [MitverwaltungController::class, 'einladungZurueckziehen'])->name('mitverwaltung.einladung.zurueckziehen');

    Route::resource('teams', TeamsController::class)->parameters(['teams' => 'team']);
    Route::resource('sponsoren', SponsorController::class)->parameters(['sponsoren' => 'sponsor'])->except('destroy');
    Route::resource('sponsorings', SponsoringController::class)->only(['index', 'create', 'store', 'destroy']);

    Route::middleware('permission:edit projekt')->group(function () {
        Route::resource('projects', ProjectsController::class)->except(['index', 'show']);
    });

    Route::middleware('permission:edit user')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'show', 'update']);
        Route::post('users/{user}/verification', [UserController::class, 'sendVerification'])->name('users.verification');
    });

    Route::middleware('permission:edit startnummer')->group(function () {
        Route::get('startnummern', [StartnummerController::class, 'index'])->name('startnummern.index');
        Route::post('startnummern', [StartnummerController::class, 'store'])->name('startnummern.store');
        Route::delete('startnummern', [StartnummerController::class, 'destroyMany'])->name('startnummern.destroy-many');
        Route::delete('startnummern/{startnummer}', [StartnummerController::class, 'destroy'])->name('startnummern.destroy');
    });

    // Dubletten bereinigen
    Route::middleware('permission:edit laeufer|edit sponsoren')->group(function () {
        Route::get('dubletten', [DublettenController::class, 'index'])->name('dubletten.index');
        Route::post('dubletten/laeufer', [DublettenController::class, 'laeufer'])->name('dubletten.laeufer');
        Route::post('dubletten/sponsoren', [DublettenController::class, 'sponsoren'])->name('dubletten.sponsoren');
        Route::post('dubletten/ignorieren', [DublettenController::class, 'ignorieren'])->name('dubletten.ignorieren');
    });

    // Veranstaltungen & Klassen
    Route::middleware('permission:edit event')->group(function () {
        Route::resource('events', EventController::class)->except(['show']);
        Route::post('events/{event}/aktivieren', [EventController::class, 'aktivieren'])->name('events.aktivieren');
        Route::post('events/{event}/wechseln', [EventController::class, 'wechseln'])->name('events.wechseln');
        Route::get('events/{event}/klassen', [SchulklasseController::class, 'index'])->name('klassen.index');
        Route::post('events/{event}/klassen', [SchulklasseController::class, 'store'])->name('klassen.store');
        Route::put('klassen/{schulklasse}', [SchulklasseController::class, 'update'])->name('klassen.update');
        Route::delete('klassen/{schulklasse}', [SchulklasseController::class, 'destroy'])->name('klassen.destroy');
    });

    // Auswertungen & Statistiken
    Route::middleware('permission:show auswertung')->prefix('auswertung')->name('auswertung.')->controller(AuswertungController::class)->group(function () {
        Route::get('/', 'uebersicht')->name('uebersicht');
        Route::get('laeufer', 'laeufer')->name('laeufer');
        Route::get('teams', 'teams')->name('teams');
        Route::get('altersgruppen', 'altersgruppen')->name('altersgruppen');
        Route::get('gruppen', 'gruppen')->name('gruppen');
        Route::get('klassen', 'klassen')->name('klassen');
        Route::get('klassen/{schulklasse}', 'klasse')->name('klasse');
        Route::get('sponsoren', 'sponsoren')->name('sponsoren');
        Route::get('projekte', 'projekte')->name('projekte');
        Route::get('jahresvergleich', 'jahresvergleich')->name('jahresvergleich');
        Route::get('export/{bereich}', 'export')->name('export');
        Route::get('pdf/{bereich}', 'pdf')->name('pdf');
    });

    // Abrechnung
    Route::middleware('permission:manage abrechnung')->group(function () {
        Route::get('abrechnung', [AbrechnungController::class, 'index'])->name('abrechnung.index');
        Route::get('abrechnung/mails', [AbrechnungController::class, 'mails'])->name('abrechnung.mails');
        Route::post('abrechnung/mails', [AbrechnungController::class, 'mailsSenden'])->name('abrechnung.mails.senden');
        Route::get('abrechnung/export/{art}', [AbrechnungController::class, 'export'])->name('abrechnung.export');
        Route::get('abrechnung/{sponsor}', [AbrechnungController::class, 'show'])->name('abrechnung.show');
        Route::post('abrechnung/{sponsor}/mail', [AbrechnungController::class, 'mailSenden'])->name('abrechnung.mail');
        Route::get('abrechnung/{sponsor}/nachweis', [AbrechnungController::class, 'nachweis'])->name('abrechnung.nachweis');
        Route::post('abrechnung/{sponsor}/zahlungen', [ZahlungController::class, 'store'])->name('zahlungen.store');
        Route::delete('zahlungen/{zahlung}', [ZahlungController::class, 'destroy'])->name('zahlungen.destroy');
    });

    // Lauftag
    Route::middleware('permission:schnellerfassung')->group(function () {
        Route::get('lauftag/schnellerfassung', [SchnellerfassungController::class, 'create'])->name('schnellerfassung.create');
        Route::post('lauftag/schnellerfassung', [SchnellerfassungController::class, 'store'])->name('schnellerfassung.store');
    });

    Route::middleware('permission:edit runden')->group(function () {
        Route::get('lauftag/runden', [RundenController::class, 'index'])->name('runden.index');
        Route::post('lauftag/runden', [RundenController::class, 'update'])->name('runden.update');
    });

    Route::middleware('permission:import export')->group(function () {
        Route::get('lauftag/import', [ImportController::class, 'create'])->name('import.create');
        Route::post('lauftag/import/datei', [ImportController::class, 'datei'])->name('import.datei');
        Route::post('lauftag/import/url', [ImportController::class, 'url'])->name('import.url');

        Route::get('export/laeufer', [ExportController::class, 'laeufer'])->name('export.laeufer');
        Route::get('export/sponsoren', [ExportController::class, 'sponsoren'])->name('export.sponsoren');
        Route::get('export/projekte', [ExportController::class, 'projekte'])->name('export.projekte');
    });

    // Online-Updater (die Pfade bleiben im Wartungsmodus erreichbar, siehe bootstrap/app.php)
    Route::middleware('permission:system update')->prefix('verwaltung/update')->name('updates.')->controller(UpdateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('pruefen', 'pruefen')->name('pruefen');
        Route::post('starten', 'starten')->name('starten');
        Route::get('status', 'status')->name('status');
    });

    // Interne JSON-Endpunkte für Suchfelder (Schnellerfassung, Rundeneingabe)
    Route::prefix('intern/api')->name('api.')->controller(SucheController::class)->group(function () {
        Route::get('laeufer', 'laeufer')->name('laeufer');
        Route::get('sponsoren', 'sponsoren')->name('sponsoren');
        Route::get('startnummer/{nummer}', 'startnummer')->whereNumber('nummer')->name('startnummer');
    });
});
