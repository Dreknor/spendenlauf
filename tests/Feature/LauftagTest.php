<?php

use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Zahlung;

beforeEach(function () {
    $this->event = aktivesEvent(['datum' => now()->toDateString()]);
    $this->helfer = admin();
});

it('erfasst Läufer, Startnummer, Sponsoren und Barzahlung in einem Schritt', function () {
    $klasse = Schulklasse::factory()->create(['event_id' => $this->event->id]);
    $bekannt = Sponsor::factory()->create();

    $this->actingAs($this->helfer)->post(route('schnellerfassung.store'), [
        'vorname' => 'Lina', 'nachname' => 'Lauf', 'kategorie' => 'schueler', 'schulklasse_id' => $klasse->id,
        'startnummer' => 77, 'teamname' => 'Die Schnellen',
        'sponsoren' => [
            ['sponsor_id' => $bekannt->id, 'rundenBetrag' => 1, 'ziel' => 'laeufer'],
            ['anrede' => 'Herr', 'vorname' => 'Opa', 'nachname' => 'Lauf', 'strasse' => 'Am Bach 2', 'plz' => '01445', 'ort' => 'Radebeul', 'festBetrag' => 20, 'bar' => 20, 'ziel' => 'team'],
        ],
    ])->assertRedirect(route('schnellerfassung.create'))->assertSessionHas('letzte_startnummer', 77);

    $lina = Laeufer::firstWhere('vorname', 'Lina');
    expect($lina->startnummer)->toBe(77)
        ->and($lina->schulklasse_id)->toBe($klasse->id)
        ->and($lina->team->name)->toBe('Die Schnellen')
        ->and(Sponsoring::count())->toBe(2)
        ->and(Sponsoring::where('sponsorable_type', 'team')->count())->toBe(1)
        ->and((float) Zahlung::sole()->betrag)->toBe(20.0);
});

it('verlangt die Anschrift neuer Sponsoren', function () {
    $this->actingAs($this->helfer)->post(route('schnellerfassung.store'), [
        'vorname' => 'Tom', 'nachname' => 'Test', 'kategorie' => 'extern',
        'sponsoren' => [['anrede' => 'Frau', 'vorname' => 'Oma', 'nachname' => 'Test', 'festBetrag' => 10]],
    ])->assertSessionHasErrors(['sponsoren.0.strasse', 'sponsoren.0.plz', 'sponsoren.0.ort']);

    expect(Sponsor::count())->toBe(0);
});

it('lehnt eine bereits vergebene Startnummer ab', function () {
    Laeufer::factory()->create(['event_id' => $this->event->id, 'startnummer' => 5]);

    $this->actingAs($this->helfer)->post(route('schnellerfassung.store'), [
        'vorname' => 'Tom', 'nachname' => 'Test', 'kategorie' => 'extern', 'startnummer' => 5,
    ])->assertSessionHasErrors('startnummer');

    expect(Laeufer::where('vorname', 'Tom')->exists())->toBeFalse();
});

it('setzt und addiert Runden manuell per JSON', function () {
    $l = Laeufer::factory()->create(['event_id' => $this->event->id, 'startnummer' => 12, 'runden' => 4]);

    $this->actingAs($this->helfer)->postJson(route('runden.update'), ['startnummer' => 12, 'runden' => 10])
        ->assertOk()->assertJson(['vorher' => 4, 'runden' => 10]);
    $this->actingAs($this->helfer)->postJson(route('runden.update'), ['startnummer' => 12, 'runden' => 1, 'modus' => 'addieren'])
        ->assertOk()->assertJson(['runden' => 11]);

    expect($l->fresh())->runden->toBe(11)->runden_manuell->toBeTrue();
});

it('meldet unbekannte Startnummern bei der Rundeneingabe', function () {
    $this->actingAs($this->helfer)->postJson(route('runden.update'), ['startnummer' => 999, 'runden' => 3])->assertNotFound();
});

it('sucht Läufer und prüft Startnummern', function () {
    Laeufer::factory()->create(['event_id' => $this->event->id, 'vorname' => 'Paula', 'nachname' => 'Pfeil', 'startnummer' => 33]);

    $this->actingAs($this->helfer)->getJson(route('api.laeufer', ['q' => 'Paula Pfeil']))->assertOk()->assertJsonPath('0.startnummer', 33);
    $this->actingAs($this->helfer)->getJson(route('api.startnummer', 33))->assertJson(['frei' => false]);
    $this->actingAs($this->helfer)->getJson(route('api.startnummer', 34))->assertJson(['frei' => true]);
});
