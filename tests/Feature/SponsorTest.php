<?php

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\User;
use App\Support\CurrentEvent;

it('verlangt beim Anlegen eines Sponsors die Anschrift', function () {
    aktivesEvent();

    $this->actingAs(User::factory()->create())->post(route('sponsoren.store'), [
        'anrede' => 'Frau', 'vorname' => 'Eva', 'nachname' => 'Muster',
    ])->assertSessionHasErrors(['strasse', 'plz', 'ort']);

    expect(Sponsor::count())->toBe(0);
});

it('zeigt in der Sponsorenliste nur die Sponsoren des angezeigten Laufs', function () {
    $alt = Event::factory()->create(['aktiv' => false, 'datum' => now()->subYear()->toDateString(), 'created_at' => now()->subYear()]);
    $neu = aktivesEvent(['created_at' => now()->subDay()]);
    $admin = admin();

    $altSponsor = Sponsor::factory()->create(['nachname' => 'Altsponsor', 'created_at' => now()->subYear()]);
    Sponsoring::factory()->fuer(Laeufer::factory()->create(['event_id' => $alt->id]))->create(['sponsor_id' => $altSponsor->id]);
    $neuSponsor = Sponsor::factory()->create(['nachname' => 'Neusponsor']);
    Sponsoring::factory()->fuer(Laeufer::factory()->create(['event_id' => $neu->id]))->create(['sponsor_id' => $neuSponsor->id]);
    // gerade angelegt, noch ohne Spende → gehört zum aktuellen Lauf
    Sponsor::factory()->create(['nachname' => 'Frischsponsor']);
    // Altbestand ohne Spende → früherer Lauf
    Sponsor::factory()->create(['nachname' => 'Karteileiche', 'created_at' => now()->subMonths(6)]);

    $this->actingAs($admin)->get(route('sponsoren.index'))
        ->assertOk()
        ->assertSee('Neusponsor')->assertSee('Frischsponsor')
        ->assertDontSee('Altsponsor')->assertDontSee('Karteileiche')
        ->assertSee('2 Sponsoren aus früheren Läufen sind ausgeblendet');

    $this->actingAs($admin)->get(route('sponsoren.index', ['filter' => 'alle']))
        ->assertSee('Neusponsor')->assertSee('Altsponsor')->assertSee('Karteileiche');

    // Wechsel auf den alten Lauf: dort erscheinen nur dessen Sponsoren
    $this->actingAs($admin)->post(route('events.wechseln', $alt));
    app(CurrentEvent::class)->forget();
    $this->actingAs($admin)->get(route('sponsoren.index'))
        ->assertSee('Altsponsor')->assertSee('Karteileiche')
        ->assertDontSee('Neusponsor')->assertDontSee('Frischsponsor');
});

it('zeigt Eltern nur ihre Sponsoren mit Spenden für die eigenen Läufer in diesem Lauf', function () {
    $alt = Event::factory()->create(['aktiv' => false, 'datum' => now()->subYear()->toDateString(), 'created_at' => now()->subYear()]);
    $neu = aktivesEvent(['created_at' => now()->subDay()]);
    $papa = User::factory()->create();

    $oma = Sponsor::factory()->create(['nachname' => 'Oma', 'created_at' => now()->subYear()]);
    $oma->users()->attach($papa->id);
    Sponsoring::factory()->fuer(Laeufer::factory()->create(['event_id' => $alt->id, 'verwaltet_von' => $papa->id]))->create(['sponsor_id' => $oma->id]);

    $this->actingAs($papa)->get(route('sponsoren.index'))
        ->assertDontSee('Oma')
        ->assertSee('Ein Sponsor aus früheren Läufen ist ausgeblendet');

    Sponsoring::factory()->fuer(Laeufer::factory()->create(['event_id' => $neu->id, 'verwaltet_von' => $papa->id]))->create(['sponsor_id' => $oma->id]);

    $this->actingAs($papa)->get(route('sponsoren.index'))
        ->assertSee('Oma')
        ->assertDontSee('ausgeblendet');
});
