<?php

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\Sponsor;
use App\Models\Teams;
use App\Models\User;
use App\Support\CurrentEvent;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(DemoSeeder::class);
    $this->event = Event::where('aktiv', true)->first();
    app(CurrentEvent::class)->set($this->event);
    $this->admin = User::where('email', 'demo-admin@example.org')->first();
});

it('zeigt alle Verwaltungsseiten fehlerfrei an', function (string $route, array $parameter = []) {
    $parameter = array_map(fn ($p) => is_callable($p) ? $p() : $p, $parameter);

    $this->actingAs($this->admin)->get(route($route, $parameter))->assertOk();
})->with([
    ['home'], ['profil'],
    ['laeufer.index'], ['laeufer.create'], ['laeufer.edit', ['laeufer' => fn () => Laeufer::first()]],
    ['teams.index'], ['teams.create'], ['teams.edit', ['team' => fn () => Teams::first()]],
    ['sponsoren.index'], ['sponsoren.create'], ['sponsoren.edit', ['sponsor' => fn () => Sponsor::has('sponsorings')->first()]],
    ['sponsorings.index'], ['sponsorings.create'], ['projects.index'], ['projects.create'],
    ['users.index'], ['users.show', ['user' => fn () => User::first()]],
    ['startnummern.index'],
    ['events.index'], ['events.create'], ['events.edit', ['event' => fn () => Event::first()]], ['klassen.index', ['event' => fn () => Event::first()]],
    ['auswertung.uebersicht'], ['auswertung.laeufer'], ['auswertung.teams'], ['auswertung.altersgruppen'],
    ['auswertung.gruppen', ['dim' => 'klasse']], ['auswertung.gruppen', ['dim' => 'stufe']], ['auswertung.gruppen', ['dim' => 'team']],
    ['auswertung.klassen'], ['auswertung.klasse', ['schulklasse' => fn () => Schulklasse::first()]],
    ['auswertung.sponsoren'], ['auswertung.projekte'], ['auswertung.jahresvergleich'],
    ['abrechnung.index'], ['abrechnung.show', ['sponsor' => fn () => Sponsor::has('sponsorings')->first()]],
    ['abrechnung.mails', ['typ' => 'aufforderung']], ['abrechnung.mails', ['typ' => 'erinnerung']],
    ['schnellerfassung.create'], ['runden.index'], ['import.create'],
]);

it('liefert Läufer-Auswertung mit Top-Ansicht und Filtern', function () {
    $this->actingAs($this->admin)
        ->get(route('auswertung.laeufer', ['ansicht' => 'top', 'nach_geschlecht' => 1, 'kategorie' => 'schueler']))
        ->assertOk();
});

it('erzeugt Excel-Exporte und PDFs', function (string $route, array $parameter) {
    $this->actingAs($this->admin)->get(route($route, $parameter))->assertOk();
})->with([
    ['auswertung.export', ['laeufer']], ['auswertung.export', ['teams']], ['auswertung.export', ['klassen']],
    ['auswertung.export', ['altersgruppen']], ['auswertung.export', ['sponsoren']], ['auswertung.export', ['projekte']],
    ['auswertung.export', ['jahresvergleich']], ['auswertung.pdf', ['uebersicht']], ['auswertung.pdf', ['klassen']],
    ['abrechnung.export', ['offen']], ['abrechnung.export', ['grossspenden']],
    ['export.laeufer', []], ['export.sponsoren', []], ['export.projekte', []],
]);

it('zeigt öffentliche Seiten ohne Anmeldung', function () {
    $laeufer = Laeufer::first();
    $sponsor = Sponsor::has('sponsorings')->first();

    $this->get('/')->assertOk()->assertSee($this->event->name);
    $this->get(route('projects.index'))->assertOk();
    $this->get(route('stats'))->assertOk()->assertHeader('Content-Security-Policy');
    $this->get(route('public.sponsor.show', $laeufer->uuid))->assertOk()->assertSee($laeufer->vorname);
    $this->get(route('public.sponsor.konto', $sponsor->uuid))->assertOk();
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

it('leitet Gäste von geschützten Seiten zur Anmeldung', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get(route('auswertung.uebersicht'))->assertRedirect(route('login'));
});

it('verweigert Seiten ohne passende Rechte', function () {
    $eltern = User::factory()->create();

    $this->actingAs($eltern)->get(route('home'))->assertOk();
    $this->actingAs($eltern)->get(route('auswertung.uebersicht'))->assertForbidden();
    $this->actingAs($eltern)->get(route('abrechnung.index'))->assertForbidden();
    $this->actingAs($eltern)->get(route('events.index'))->assertForbidden();
    $this->actingAs($eltern)->get(route('schnellerfassung.create'))->assertForbidden();
});

it('zeigt die öffentliche Statistik auch aus dem persistenten Cache', function () {
    config(['cache.default' => 'file']);
    Cache::flush();

    $this->get(route('stats'))->assertOk();
    $this->get(route('stats'))->assertOk();

    Cache::flush();
});
