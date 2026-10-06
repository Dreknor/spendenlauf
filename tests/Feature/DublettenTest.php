<?php

use App\Actions\Zusammenfuehren;
use App\Mail\MitverwaltungAngefragt;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Startnummer;
use App\Models\User;
use App\Models\Zahlung;
use App\Services\Dubletten;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->event = aktivesEvent();
    $this->mama = User::factory()->create();
    $this->papa = User::factory()->create();
    $this->kind = Laeufer::factory()->create([
        'event_id' => $this->event->id, 'verwaltet_von' => $this->mama->id,
        'vorname' => 'Jörg', 'nachname' => 'Müller', 'geburtsdatum' => '2015-03-04',
    ]);
});

function anmeldung(array $extra = []): array
{
    return array_merge([
        'vorname' => 'Jörg', 'nachname' => 'Müller', 'geburtsdatum' => '2015-03-04', 'kategorie' => 'eltern',
        'datenschutz' => '1', 'bilder' => '1',
    ], $extra);
}

// --- Erkennung -----------------------------------------------------------

it('erkennt gleiche Läufer trotz anderer Schreibweise', function (string $vor, string $nach, string $grad) {
    $treffer = Dubletten::laeuferKandidaten($this->event, $vor, $nach, '2015-03-04');

    expect($treffer->first()['grad'] ?? null)->toBe($grad);
})->with([
    ['Jörg', 'Müller', 'sicher'],
    [' joerg ', 'MUELLER', 'sicher'],
    ['Jörg', 'Mueller', 'sicher'],
    ['Joerg', 'Möller', 'aehnlich'],
    ['Müller', 'Jörg', 'aehnlich'],
]);

it('hält Geschwister mit anderem Vornamen oder Geburtsdatum auseinander', function () {
    expect(Dubletten::laeuferKandidaten($this->event, 'Anna', 'Müller', '2017-08-01'))->toBeEmpty()
        ->and(Dubletten::laeuferKandidaten($this->event, 'Jörg', 'Müller', '2019-11-20'))->toBeEmpty();
});

it('erkennt einen Tippfehler im Geburtsdatum als ähnlich', function () {
    expect(Dubletten::laeuferKandidaten($this->event, 'Jörg', 'Müller', '2015-03-05')->first()['grad'])->toBe('aehnlich');
});

// --- Anmeldung -------------------------------------------------------------

it('verhindert die doppelte Anmeldung durch den anderen Elternteil', function () {
    $this->actingAs($this->papa)->post(route('laeufer.store'), anmeldung(['nachname' => 'Mueller', 'trotzdem_anlegen' => '1']))
        ->assertRedirect()
        ->assertSessionHas('dubletten')
        ->assertSessionHas('dubletten_uebergehbar', false);

    expect(Laeufer::count())->toBe(1);
});

it('lässt bei ähnlichen Namen die Bestätigung „andere Person“ zu', function () {
    $this->actingAs($this->papa)->post(route('laeufer.store'), anmeldung(['nachname' => 'Möller']))
        ->assertSessionHas('dubletten_uebergehbar', true);

    $this->actingAs($this->papa)->post(route('laeufer.store'), anmeldung(['nachname' => 'Möller', 'trotzdem_anlegen' => '1']))
        ->assertSessionHasNoErrors();

    expect(Laeufer::count())->toBe(2);
});

it('erlaubt dem Orga-Team die Anmeldung trotz gleichem Namen', function () {
    $this->actingAs(admin())->post(route('laeufer.store'), anmeldung(['trotzdem_anlegen' => '1']));

    expect(Laeufer::count())->toBe(2);
});

// --- Mitverwaltung -----------------------------------------------------------

it('lässt den zweiten Elternteil die Mitverwaltung anfragen und nach Bestätigung bearbeiten', function () {
    Mail::fake();

    $this->actingAs($this->papa)->get(route('laeufer.edit', $this->kind))->assertForbidden();
    $this->actingAs($this->papa)->post(route('mitverwaltung.anfragen', $this->kind))->assertRedirect(route('home'));

    Mail::assertQueued(MitverwaltungAngefragt::class, fn ($m) => $m->hasTo($this->mama->email));
    $anfrage = MitverwaltungAnfrage::sole();

    // Nur der anmeldende Elternteil (oder das Orga-Team) darf bestätigen
    $this->actingAs($this->papa)->post(route('mitverwaltung.annehmen', $anfrage))->assertForbidden();
    $this->actingAs($this->mama)->get(route('home'))->assertSee($this->papa->name);
    $this->actingAs($this->mama)->post(route('mitverwaltung.annehmen', $anfrage))->assertRedirect();

    expect($anfrage->fresh()->status)->toBe('angenommen');
    $this->actingAs($this->papa)->get(route('laeufer.edit', $this->kind))->assertOk();
    $this->actingAs($this->papa)->get(route('laeufer.index'))->assertSee('Müller');
});

it('lässt das Orga-Team ein weiteres Konto direkt zuordnen und wieder entfernen', function () {
    $orga = admin();

    $this->actingAs($orga)->post(route('mitverwaltung.hinzufuegen', $this->kind), ['email' => $this->papa->email])->assertSessionHas('type', 'success');
    expect($this->kind->istVerwaltetVon($this->papa))->toBeTrue();

    $this->actingAs($this->papa)->delete(route('mitverwaltung.entfernen', [$this->kind, $this->papa]));
    expect($this->kind->fresh()->istVerwaltetVon($this->papa))->toBeFalse();
});

it('fragt bei der Vorjahres-Übernahme die Mitverwaltung an statt doppelt anzulegen', function () {
    Mail::fake();
    $alt = Event::factory()->create(['aktiv' => false, 'datum' => now()->subYears(2)]);
    $vorjahr = Laeufer::factory()->create(['event_id' => $alt->id, 'verwaltet_von' => $this->papa->id, 'vorname' => 'Jörg', 'nachname' => 'Müller', 'geburtsdatum' => '2015-03-04']);

    $this->actingAs($this->papa)->post(route('laeufer.uebernehmen'), ['laeufer' => [$vorjahr->id]])
        ->assertSessionHas('type', 'warning');

    expect(Laeufer::count())->toBe(1)
        ->and(MitverwaltungAnfrage::where('user_id', $this->papa->id)->exists())->toBeTrue();
});

it('zeigt in der Schnellerfassung vorhandene Läufer an', function () {
    $this->actingAs(admin())->post(route('schnellerfassung.store'), ['vorname' => 'Joerg', 'nachname' => 'Müller', 'kategorie' => 'eltern', 'geburtsdatum' => '2015-03-04'])
        ->assertSessionHas('dubletten');

    expect(Laeufer::count())->toBe(1);
});

// --- Sponsoren -----------------------------------------------------------------

it('verwendet einen vorhandenen Sponsor statt „E-Mail bereits vergeben“', function () {
    $oma = Sponsor::factory()->create(['email' => 'oma@example.org', 'vorname' => 'Erika', 'nachname' => 'Muster']);
    $oma->users()->attach($this->mama);

    $this->actingAs($this->papa)->post(route('sponsoren.store'), [
        'anrede' => 'Frau', 'vorname' => 'Erika', 'nachname' => 'Muster', 'email' => 'OMA@example.org',
        'strasse' => 'Weg 1', 'plz' => '01445', 'ort' => 'Radebeul',
    ])->assertSessionHasNoErrors()->assertSessionHas('type', 'success');

    expect(Sponsor::count())->toBe(1)
        ->and($oma->users()->pluck('users.id')->sort()->values()->all())->toBe(collect([$this->mama->id, $this->papa->id])->sort()->values()->all());
});

it('fragt bei ähnlichen Sponsoren nach und übernimmt die Auswahl', function () {
    $opa = Sponsor::factory()->create(['email' => null, 'vorname' => 'Heinz', 'nachname' => 'Schmidt', 'plz' => '01445']);
    $daten = ['anrede' => 'Herr', 'vorname' => 'Heinz', 'nachname' => 'Schmitt', 'strasse' => 'Weg 1', 'plz' => '01445', 'ort' => 'Radebeul'];

    $this->actingAs($this->papa)->post(route('sponsoren.store'), $daten)->assertSessionHas('sponsor_dubletten');
    expect(Sponsor::count())->toBe(1);

    $this->actingAs($this->papa)->post(route('sponsoren.store'), $daten + ['vorhandener_sponsor' => $opa->id]);
    expect(Sponsor::count())->toBe(1)->and($opa->users()->whereKey($this->papa->id)->exists())->toBeTrue();

    $this->actingAs($this->papa)->post(route('sponsoren.store'), $daten + ['trotzdem_anlegen' => '1']);
    expect(Sponsor::count())->toBe(2);
});

it('zeigt Eltern bei gemeinsamen Sponsoren nur die Spenden für eigene Läufer', function () {
    $oma = Sponsor::factory()->create();
    $oma->users()->attach([$this->mama->id, $this->papa->id]);
    $fremdesKind = Laeufer::factory()->runden(10)->create(['event_id' => $this->event->id, 'verwaltet_von' => $this->papa->id, 'nachname' => 'Fremdkind']);
    Sponsoring::factory()->fuer($this->kind)->create(['sponsor_id' => $oma->id, 'festBetrag' => 11, 'rundenBetrag' => 0]);
    Sponsoring::factory()->fuer($fremdesKind)->create(['sponsor_id' => $oma->id, 'festBetrag' => 77, 'rundenBetrag' => 0]);

    $this->actingAs($this->mama)->get(route('sponsoren.edit', $oma))->assertOk()->assertSee('11,00 €')->assertDontSee('77,00 €')->assertDontSee('Fremdkind');
    $this->actingAs($this->mama)->get(route('sponsoren.index'))->assertSee('11,00 €')->assertDontSee('88,00 €');
    $this->actingAs($this->mama)->get(route('sponsorings.index'))->assertDontSee('Fremdkind');
});

it('verwendet in der Schnellerfassung vorhandene Sponsoren über die E-Mail', function () {
    Sponsor::factory()->create(['email' => 'bekannt@example.org']);

    $this->actingAs(admin())->post(route('schnellerfassung.store'), [
        'vorname' => 'Neu', 'nachname' => 'Kind', 'kategorie' => 'extern',
        'sponsoren' => [['anrede' => 'Herr', 'vorname' => 'X', 'nachname' => 'Y', 'email' => 'bekannt@example.org', 'strasse' => 'a', 'plz' => '1', 'ort' => 'b', 'festBetrag' => 5]],
    ])->assertSessionHasNoErrors();

    expect(Sponsor::count())->toBe(1)->and(Sponsoring::count())->toBe(1);
});

// --- Zusammenführen ---------------------------------------------------------

it('führt zwei Läufer zusammen und übernimmt Spenden, Verwalter und Daten', function () {
    $doppelt = Laeufer::factory()->create([
        'event_id' => $this->event->id, 'verwaltet_von' => $this->papa->id, 'vorname' => 'Joerg', 'nachname' => 'Müller',
        'geburtsdatum' => null, 'runden' => 25, 'startnummer' => 500,
    ]);
    Sponsoring::factory()->fuer($doppelt)->create();

    expect(Dubletten::laeuferPaare($this->event))->toHaveCount(1);

    $this->actingAs(admin())->post(route('dubletten.laeufer'), ['behalten' => $this->kind->id, 'entfernen' => $doppelt->id])->assertRedirect();

    $kind = $this->kind->fresh();
    expect($kind->runden)->toBe(25)
        ->and($kind->sponsorings()->count())->toBe(1)
        ->and($kind->istVerwaltetVon($this->papa))->toBeTrue()
        ->and(Laeufer::find($doppelt->id))->toBeNull()
        ->and(Startnummer::where('startnummer', 500)->exists())->toBeTrue()
        ->and(Dubletten::laeuferPaare($this->event))->toBeEmpty();
});

it('führt zwei Sponsoren samt Zahlungen zusammen', function () {
    $a = Sponsor::factory()->create(['email' => null, 'vorname' => 'Heinz', 'nachname' => 'Schmidt', 'plz' => '01445']);
    $b = Sponsor::factory()->create(['email' => 'heinz@example.org', 'vorname' => 'Heinz', 'nachname' => 'Schmidt', 'plz' => '01445']);
    $b->users()->attach($this->papa);
    Sponsoring::factory()->fuer($this->kind)->create(['sponsor_id' => $b->id]);
    Zahlung::factory()->create(['event_id' => $this->event->id, 'sponsor_id' => $b->id]);

    (new Zusammenfuehren)->sponsor($a, $b);

    $a->refresh();
    expect($a->email)->toBe('heinz@example.org')
        ->and($a->sponsorings()->count())->toBe(1)
        ->and($a->zahlungen()->count())->toBe(1)
        ->and($a->users()->whereKey($this->papa->id)->exists())->toBeTrue()
        ->and(Sponsor::find($b->id))->toBeNull();
});

it('blendet als „keine Dublette“ markierte Paare aus', function () {
    $zwilling = Laeufer::factory()->create(['event_id' => $this->event->id, 'vorname' => 'Jörg', 'nachname' => 'Müller', 'geburtsdatum' => null]);
    expect(Dubletten::laeuferPaare($this->event))->toHaveCount(1);

    $this->actingAs(admin())->post(route('dubletten.ignorieren'), ['typ' => 'laeufer', 'a' => $zwilling->id, 'b' => $this->kind->id]);

    expect(Dubletten::laeuferPaare($this->event))->toBeEmpty();
});

it('zeigt die Dubletten-Seite nur dem Orga-Team', function () {
    $this->actingAs($this->mama)->get(route('dubletten.index'))->assertForbidden();
    $this->actingAs(admin())->get(route('dubletten.index'))->assertOk();
    $this->actingAs(admin())->get(route('dubletten.index', ['art' => 'sponsoren']))->assertOk();
});
