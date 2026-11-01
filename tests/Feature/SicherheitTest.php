<?php

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Models\User;
use App\Support\CurrentEvent;
use Illuminate\Support\Facades\Mail;

function sponsorDaten(array $extra = []): array
{
    return array_merge([
        'anrede' => 'Frau', 'vorname' => 'Oma', 'nachname' => 'Erna', 'email' => 'oma@example.org',
        'strasse' => 'Lindenweg 3', 'plz' => '01445', 'ort' => 'Radebeul',
    ], $extra);
}

it('lässt nur den Erfasser eines Sponsors dessen Kontaktdaten sehen und ändern', function () {
    aktivesEvent();
    $erste = User::factory()->create();
    $zweite = User::factory()->create();

    $this->actingAs($erste)->post(route('sponsoren.store'), sponsorDaten());
    $sponsor = Sponsor::sole();

    // Zweite Familie gibt dieselbe E-Mail ein → wird zugeordnet, darf Spenden eintragen …
    $this->actingAs($zweite)->post(route('sponsoren.store'), sponsorDaten(['vorname' => 'Erna']));
    expect(Sponsor::count())->toBe(1)->and($sponsor->fresh()->wirdVerwaltetVon($zweite))->toBeTrue();

    // … sieht aber weder Anschrift noch E-Mail und kann sie nicht ändern
    $this->actingAs($zweite)->get(route('sponsoren.edit', $sponsor))
        ->assertOk()->assertDontSee('Lindenweg 3')->assertDontSee('oma@example.org');
    $this->actingAs($zweite)->put(route('sponsoren.update', $sponsor), sponsorDaten(['email' => 'angreifer@example.org']))
        ->assertForbidden();
    expect($sponsor->fresh()->email)->toBe('oma@example.org');

    $this->actingAs($erste)->put(route('sponsoren.update', $sponsor), sponsorDaten(['telefon' => '0351 123']))
        ->assertSessionHas('type', 'success');
    expect($sponsor->fresh()->telefon)->toBe('0351 123');
});

it('verhindert, dass "Benutzer verwalten" Admin-Konten übernimmt', function () {
    aktivesEvent();
    $verwalter = User::factory()->create()->givePermissionTo('edit user');
    $admin = admin();

    $this->actingAs($verwalter)->put(route('users.update', $admin), [
        'vorname' => $admin->vorname, 'nachname' => $admin->nachname, 'email' => 'neu@example.org',
    ])->assertSessionHas('type', 'danger');

    expect($admin->fresh()->email)->not->toBe('neu@example.org');
});

it('setzt bei geänderter E-Mail die Bestätigung zurück', function () {
    aktivesEvent();
    $nutzer = User::factory()->create();

    $this->actingAs(admin())->put(route('users.update', $nutzer), [
        'vorname' => $nutzer->vorname, 'nachname' => $nutzer->nachname, 'email' => 'neu@example.org',
    ])->assertSessionHas('type', 'success');

    expect($nutzer->fresh()->email_verified_at)->toBeNull();
});

it('nimmt nach Anmeldeschluss auch per direktem POST keine Spenden an', function () {
    $event = aktivesEvent(['datum' => now()->subDays(2)->toDateString()]);
    $eltern = User::factory()->create();
    $laeufer = Laeufer::factory()->create(['event_id' => $event->id, 'verwaltet_von' => $eltern->id]);
    $sponsor = Sponsor::factory()->create();
    $sponsor->users()->attach($eltern);

    $this->actingAs($eltern)->post(route('sponsorings.store'), [
        'sponsor_id' => $sponsor->id, 'type' => 'laeufer', 'laeufer_id' => $laeufer->id, 'festBetrag' => 10,
    ])->assertSessionHas('type', 'danger');

    expect(Sponsoring::count())->toBe(0);
});

it('akzeptiert nur Projekte der aktuellen Veranstaltung', function () {
    $event = aktivesEvent();
    $eigenes = Projects::factory()->create();
    $fremdes = Projects::factory()->create();
    $event->projects()->attach($eigenes);
    $eltern = User::factory()->create();
    $laeufer = Laeufer::factory()->create(['event_id' => $event->id, 'verwaltet_von' => $eltern->id]);
    $sponsor = Sponsor::factory()->create();
    $sponsor->users()->attach($eltern);

    $this->actingAs($eltern)->post(route('sponsorings.store'), [
        'sponsor_id' => $sponsor->id, 'type' => 'laeufer', 'laeufer_id' => $laeufer->id, 'festBetrag' => 10,
        'projects' => [$fremdes->id],
    ])->assertSessionHasErrors('projects.0');

    $this->actingAs($eltern)->post(route('sponsorings.store'), [
        'sponsor_id' => $sponsor->id, 'type' => 'laeufer', 'laeufer_id' => $laeufer->id, 'festBetrag' => 10,
        'projects' => [$eigenes->id],
    ])->assertSessionHasNoErrors();
});

it('leitet nach dem Speichern nicht auf fremde Seiten weiter', function () {
    $event = aktivesEvent();
    $eltern = User::factory()->create();
    $laeufer = Laeufer::factory()->create(['event_id' => $event->id, 'verwaltet_von' => $eltern->id]);
    $sponsor = Sponsor::factory()->create();
    $sponsor->users()->attach($eltern);

    $this->actingAs($eltern)->post(route('sponsorings.store'), [
        'sponsor_id' => $sponsor->id, 'type' => 'laeufer', 'laeufer_id' => $laeufer->id, 'festBetrag' => 10,
        'zurueck' => 'https://boese.example.com/phishing',
    ])->assertRedirect(route('sponsorings.index'));
});

it('sperrt Teamwechsel und Löschen von Spenden für Eltern nach dem Lauf', function () {
    $event = aktivesEvent(['datum' => now()->subDays(2)->toDateString()]);
    $eltern = User::factory()->create();
    $team = Teams::factory()->create(['event_id' => $event->id]);
    $laeufer = Laeufer::factory()->create(['event_id' => $event->id, 'verwaltet_von' => $eltern->id, 'team_id' => $team->id]);
    $sponsoring = Sponsoring::factory()->fuer($laeufer)->create(['verwaltet_von' => $eltern->id]);

    $this->actingAs($eltern)->delete(route('laeufer.team.leave', $laeufer))->assertSessionHas('type', 'danger');
    $this->actingAs($eltern)->delete(route('sponsorings.destroy', $sponsoring))->assertSessionHas('type', 'danger');

    expect($laeufer->fresh()->team_id)->toBe($team->id)
        ->and(Sponsoring::whereKey($sponsoring->id)->exists())->toBeTrue();
});

it('verschickt keine Zahlungsaufforderung an Sponsoren ohne offenen Betrag', function () {
    Mail::fake();
    $event = aktivesEvent(['datum' => now()->subDays(2)->toDateString()]);
    $laeufer = Laeufer::factory()->runden(10)->create(['event_id' => $event->id]);
    $sponsoring = Sponsoring::factory()->fuer($laeufer)->create(['rundenBetrag' => 1]);
    $sponsoring->sponsor->zahlungen()->create(['event_id' => $event->id, 'betrag' => 10, 'bezahlt_am' => now(), 'art' => 'bar']);

    $this->actingAs(admin())->post(route('abrechnung.mails.senden'), ['typ' => 'erinnerung', 'sponsoren' => [$sponsoring->sponsor_id]]);

    Mail::assertNothingQueued();
});

it('begrenzt soziale Abgabe und Klassenkasse zusammen auf 100 %', function () {
    $event = aktivesEvent();

    $this->actingAs(admin())->put(route('events.update', $event), array_merge($event->only([
        'name', 'datum', 'runden_laenge_m', 'dauer_minuten', 'soziale_abgabe_empfaenger', 'verwendungszweck_prefix',
    ]), ['datum' => $event->datum->toDateString(), 'soziale_abgabe_prozent' => 60, 'klassenkasse_prozent' => 50]))
        ->assertSessionHasErrors('klassenkasse_prozent');
});

it('setzt Sicherheits-Header, lässt die Einbettung der Statistik aber zu', function () {
    aktivesEvent();
    config(['spendenlauf.stats.content_security_policy' => "frame-ancestors 'self' https://www.example.org", 'spendenlauf.stats.x_frame_options' => '']);

    $this->get(route('login'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get(route('stats'))
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self' https://www.example.org")
        ->assertHeaderMissing('X-Frame-Options');
});

it('übernimmt beim Übernehmen aus dem Vorjahr auch die Mitverwalter', function () {
    $alt = Event::factory()->create(['aktiv' => false, 'datum' => now()->subYear()->toDateString()]);
    $neu = aktivesEvent();
    $mutter = User::factory()->create();
    $vater = User::factory()->create();
    $kind = Laeufer::factory()->create(['event_id' => $alt->id, 'verwaltet_von' => $mutter->id]);
    $kind->mitverwalter()->attach($vater);

    $this->actingAs($vater)->post(route('laeufer.uebernehmen'), ['laeufer' => [$kind->id]]);

    app(CurrentEvent::class)->set($neu);
    $uebernommen = Laeufer::where('vorname', $kind->vorname)->where('nachname', $kind->nachname)->sole();
    expect($uebernommen->istVerwaltetVon($vater))->toBeTrue()
        ->and($uebernommen->istVerwaltetVon($mutter))->toBeTrue();
});
