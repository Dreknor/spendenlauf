<?php

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\Startnummer;
use App\Models\User;

beforeEach(function () {
    $this->event = aktivesEvent();
    $this->user = User::factory()->create();
});

function laeuferDaten(array $extra = []): array
{
    return array_merge([
        'vorname' => 'Mia', 'nachname' => 'Muster', 'geburtsdatum' => '2015-05-01', 'kategorie' => 'eltern',
        'datenschutz' => '1', 'bilder' => '1',
    ], $extra);
}

it('meldet einen Läufer an und vergibt die kleinste freie Startnummer aus dem Pool', function () {
    Startnummer::insert([
        ['event_id' => $this->event->id, 'startnummer' => 105],
        ['event_id' => $this->event->id, 'startnummer' => 101],
    ]);

    $this->actingAs($this->user)->post(route('laeufer.store'), laeuferDaten())->assertRedirect();

    $l = Laeufer::firstWhere('vorname', 'Mia');
    expect($l->startnummer)->toBe(101)
        ->and($l->event_id)->toBe($this->event->id)
        ->and($l->verwaltet_von)->toBe($this->user->id)
        ->and(Startnummer::pluck('startnummer')->all())->toBe([105]);
});

it('zählt bei leerem Pool nach der höchsten Startnummer weiter', function () {
    Laeufer::factory()->create(['event_id' => $this->event->id, 'startnummer' => 42]);

    $this->actingAs($this->user)->post(route('laeufer.store'), laeuferDaten())->assertRedirect();

    expect(Laeufer::firstWhere('vorname', 'Mia')->startnummer)->toBe(43);
});

it('verlangt bei Schülern eine Klasse des aktuellen Events', function () {
    $this->actingAs($this->user)->post(route('laeufer.store'), laeuferDaten(['kategorie' => 'schueler']))
        ->assertSessionHasErrors('schulklasse_id');

    $fremd = Schulklasse::factory()->create();
    $this->actingAs($this->user)->post(route('laeufer.store'), laeuferDaten(['kategorie' => 'schueler', 'schulklasse_id' => $fremd->id]))
        ->assertSessionHasErrors('schulklasse_id');

    $klasse = Schulklasse::factory()->create(['event_id' => $this->event->id]);
    $this->actingAs($this->user)->post(route('laeufer.store'), laeuferDaten(['kategorie' => 'schueler', 'schulklasse_id' => $klasse->id]))
        ->assertSessionHasNoErrors();

    expect(Laeufer::first()->schulklasse_id)->toBe($klasse->id);
});

it('verhindert, dass fremde Läufer bearbeitet werden', function () {
    $fremd = Laeufer::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->user)->get(route('laeufer.edit', $fremd))->assertForbidden();
    $this->actingAs($this->user)->put(route('laeufer.update', $fremd), laeuferDaten())->assertForbidden();
    $this->actingAs($this->user)->delete(route('laeufer.team.leave', $fremd))->assertForbidden();
});

it('lässt normale Benutzer Startnummer und Runden nicht ändern', function () {
    $l = Laeufer::factory()->create(['event_id' => $this->event->id, 'verwaltet_von' => $this->user->id, 'startnummer' => 7, 'runden' => 3]);

    $this->actingAs($this->user)->put(route('laeufer.update', $l), laeuferDaten(['startnummer' => 1, 'runden' => 99]))->assertRedirect();

    expect($l->fresh())->startnummer->toBe(7)->runden->toBe(3)->vorname->toBe('Mia');
});

it('zeigt nur Läufer der aktuellen Veranstaltung', function () {
    $alt = Event::factory()->create(['aktiv' => false, 'datum' => '2023-06-01']);
    Laeufer::factory()->create(['event_id' => $alt->id, 'verwaltet_von' => $this->user->id, 'nachname' => 'Vorjahr']);
    Laeufer::factory()->create(['event_id' => $this->event->id, 'verwaltet_von' => $this->user->id, 'nachname' => 'Aktuell']);

    $this->actingAs($this->user)->get(route('laeufer.index'))->assertSee('Aktuell')->assertDontSee('Vorjahr');
});

it('übernimmt Läufer aus dem Vorjahr mit hochgestufter Klasse', function () {
    $alt = Event::factory()->create(['aktiv' => false, 'datum' => $this->event->datum->copy()->subYears(2)]);
    $alteKlasse = Schulklasse::factory()->create(['event_id' => $alt->id, 'name' => '5a', 'stufe' => 5]);
    $neueKlasse = Schulklasse::factory()->create(['event_id' => $this->event->id, 'name' => '7a', 'stufe' => 7]);
    $vorjahr = Laeufer::factory()->schueler($alteKlasse)->create(['event_id' => $alt->id, 'verwaltet_von' => $this->user->id, 'runden' => 30]);

    $this->actingAs($this->user)->post(route('laeufer.uebernehmen'), ['laeufer' => [$vorjahr->id]])->assertRedirect();

    $neu = Laeufer::where('vorname', $vorjahr->vorname)->first();
    expect($neu->event_id)->toBe($this->event->id)
        ->and($neu->schulklasse_id)->toBe($neueKlasse->id)
        ->and($neu->runden)->toBe(0)
        ->and($neu->uuid)->not->toBe($vorjahr->uuid);
});
