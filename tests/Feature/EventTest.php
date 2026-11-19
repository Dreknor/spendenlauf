<?php

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Startnummer;
use App\Models\Teams;
use App\Models\User;
use App\Models\Zahlung;
use App\Support\CurrentEvent;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = admin();
    $this->alt = aktivesEvent(['name' => '7. Radebeuler Spendenlauf', 'nummer' => 7, 'datum' => now()->subYear()->toDateString()]);
});

function neuerLaufDaten(Event $vorlage, array $extra = []): array
{
    return array_merge([
        'name' => '8. Radebeuler Spendenlauf', 'nummer' => 8, 'datum' => now()->addYear()->toDateString(),
        'runden_laenge_m' => 400, 'dauer_minuten' => 120, 'soziale_abgabe_prozent' => 0, 'klassenkasse_prozent' => 10,
        'verwendungszweck_prefix' => 'Spendenlauf', 'zahlungsziel_tage' => 14,
        'vorlage_event_id' => $vorlage->id, 'klassen_uebernehmen' => '1',
    ], $extra);
}

/** Lauf mit Läufer, Team, Spende, Zahlung und Startnummer; der Sponsor kommt nur hier vor. */
function befuellterLauf(Event $event, ?Sponsor $sponsor = null): array
{
    $team = Teams::factory()->create(['event_id' => $event->id]);
    $laeufer = Laeufer::factory()->create(['event_id' => $event->id, 'team_id' => $team->id]);
    $sponsor ??= Sponsor::factory()->create();
    $sponsoring = Sponsoring::factory()->fuer($laeufer)->create(['sponsor_id' => $sponsor->id]);
    Zahlung::factory()->create(['event_id' => $event->id, 'sponsor_id' => $sponsor->id]);
    Startnummer::insert(['event_id' => $event->id, 'startnummer' => 500]);

    return compact('team', 'laeufer', 'sponsor', 'sponsoring');
}

it('übernimmt beim Anlegen eines neuen Laufs keine Läufer und Teams und zeigt dem Admin den neuen Lauf', function () {
    $altdaten = befuellterLauf($this->alt);
    $this->actingAs($this->admin)->get(route('laeufer.index'))->assertSee($altdaten['laeufer']->nachname);

    $this->actingAs($this->admin)->post(route('events.store'), neuerLaufDaten($this->alt))
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('status', fn ($m) => str_contains($m, 'nicht übernommen'));

    $neu = Event::firstWhere('nummer', 8);
    expect($neu->aktiv)->toBeFalse()
        ->and($this->alt->fresh()->aktiv)->toBeTrue()
        ->and(Laeufer::forEvent($neu)->count())->toBe(0)
        ->and(Teams::forEvent($neu)->count())->toBe(0)
        ->and(Sponsoring::forEvent($neu)->count())->toBe(0)
        ->and(Laeufer::forEvent($this->alt)->count())->toBe(1)
        ->and(session(CurrentEvent::SESSION_KEY))->toBe($neu->id);

    // Nächster Request: die Läuferliste zeigt den neuen, leeren Lauf
    app(CurrentEvent::class)->forget();
    $this->actingAs($this->admin)->get(route('laeufer.index'))
        ->assertOk()
        ->assertDontSee($altdaten['laeufer']->nachname);
});

it('aktiviert den neuen Lauf auf Wunsch sofort für alle', function () {
    $this->actingAs($this->admin)->post(route('events.store'), neuerLaufDaten($this->alt, ['aktivieren' => '1']))->assertRedirect();

    expect(Event::firstWhere('nummer', 8)->aktiv)->toBeTrue()
        ->and($this->alt->fresh()->aktiv)->toBeFalse()
        ->and(session(CurrentEvent::SESSION_KEY))->toBeNull();
});

it('bereinigt einen noch nicht gelaufenen Lauf und behält Benutzerkonten und Daten anderer Läufe', function () {
    $neu = Event::factory()->create(['aktiv' => false, 'datum' => now()->addYear()->toDateString()]);
    $stammSponsor = Sponsor::factory()->create();
    $altdaten = befuellterLauf($this->alt, $stammSponsor);
    $neudaten = befuellterLauf($neu);
    // Stammsponsor spendet in beiden Läufen → bleibt erhalten
    Sponsoring::factory()->fuer($neudaten['laeufer'])->create(['sponsor_id' => $stammSponsor->id]);
    $mama = User::factory()->create();
    $neudaten['laeufer']->mitverwalter()->attach($mama->id);
    $neudaten['sponsor']->users()->attach($mama->id);
    Laeufer::factory()->create(['event_id' => $neu->id])->delete();
    $anzahlBenutzer = User::count();

    $this->actingAs($this->admin)->get(route('events.bereinigen', $neu))
        ->assertOk()
        ->assertSee('Läufer (inkl. Mitverwaltungen');

    $this->actingAs($this->admin)->post(route('events.bereinigen.ausfuehren', $neu), ['bestaetigung' => $neu->name])
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('type', 'success');

    expect(Laeufer::forEvent($neu)->withTrashed()->count())->toBe(0)
        ->and(Teams::forEvent($neu)->count())->toBe(0)
        ->and(Sponsoring::forEvent($neu)->count())->toBe(0)
        ->and(Zahlung::forEvent($neu)->count())->toBe(0)
        ->and(Startnummer::forEvent($neu)->count())->toBe(0)
        ->and(DB::table('laeufer_user')->count())->toBe(0)
        ->and(Sponsor::find($neudaten['sponsor']->id))->toBeNull()
        ->and(DB::table('sponsor_user')->where('sponsor_id', $neudaten['sponsor']->id)->exists())->toBeFalse()
        ->and(Sponsor::find($stammSponsor->id))->not->toBeNull()
        ->and(User::count())->toBe($anzahlBenutzer)
        ->and($neu->fresh())->not->toBeNull();

    // Alter Lauf unverändert
    expect(Laeufer::forEvent($this->alt)->count())->toBe(1)
        ->and(Teams::forEvent($this->alt)->count())->toBe(1)
        ->and(Sponsoring::forEvent($this->alt)->count())->toBe(1)
        ->and(Zahlung::forEvent($this->alt)->count())->toBe(1)
        ->and(Startnummer::forEvent($this->alt)->count())->toBe(1)
        ->and($altdaten['laeufer']->fresh()->team_id)->toBe($altdaten['team']->id);
});

it('lässt vergangene Läufe nicht bereinigen', function () {
    befuellterLauf($this->alt);

    $this->actingAs($this->admin)->get(route('events.bereinigen', $this->alt))
        ->assertRedirect(route('events.index'))
        ->assertSessionHas('type', 'danger');

    $this->actingAs($this->admin)->post(route('events.bereinigen.ausfuehren', $this->alt), ['bestaetigung' => $this->alt->name])
        ->assertSessionHas('type', 'danger');

    expect(Laeufer::forEvent($this->alt)->count())->toBe(1)
        ->and(Sponsoring::forEvent($this->alt)->count())->toBe(1)
        ->and(Zahlung::forEvent($this->alt)->count())->toBe(1);

    $this->actingAs($this->admin)->get(route('events.index'))->assertDontSee(route('events.bereinigen', $this->alt));
});

it('bereinigt nur mit dem richtigen Namen als Bestätigung', function () {
    $neu = Event::factory()->create(['aktiv' => false, 'datum' => now()->addYear()->toDateString()]);
    befuellterLauf($neu);

    $this->actingAs($this->admin)->post(route('events.bereinigen.ausfuehren', $neu), ['bestaetigung' => 'ja'])
        ->assertSessionHasErrors('bestaetigung');

    expect(Laeufer::forEvent($neu)->count())->toBe(1);
});

it('erlaubt das Bereinigen nur dem Orga-Team', function () {
    $neu = Event::factory()->create(['aktiv' => false, 'datum' => now()->addYear()->toDateString()]);
    befuellterLauf($neu);

    $this->actingAs(User::factory()->create())->post(route('events.bereinigen.ausfuehren', $neu), ['bestaetigung' => $neu->name])
        ->assertForbidden();

    expect(Laeufer::forEvent($neu)->count())->toBe(1);
});
