<?php

use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\User;

beforeEach(function () {
    $this->event = aktivesEvent();
    $this->laeufer = Laeufer::factory()->create(['event_id' => $this->event->id]);
});

function sponsorFormular(array $extra = []): array
{
    return array_merge([
        'anrede' => 'Frau', 'vorname' => 'Erika', 'nachname' => 'Mustermann', 'email' => 'erika@example.org',
        'strasse' => 'Hauptstr. 1', 'plz' => '01445', 'ort' => 'Radebeul', 'rundenBetrag' => '1.50', 'datenschutz' => '1',
    ], $extra);
}

it('legt Sponsor und Sponsoring an, ohne ein Benutzerkonto zu erzeugen', function () {
    $users = User::count();
    $projekt = Projects::factory()->create();
    $this->event->projects()->attach($projekt);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular())->assertRedirect()->assertSessionHas('status');

    $sponsoring = Sponsoring::first();
    expect(Sponsor::count())->toBe(1)
        ->and($sponsoring->sponsorable_id)->toBe($this->laeufer->id)
        ->and((float) $sponsoring->rundenBetrag)->toBe(1.5)
        ->and($sponsoring->verwaltet_von)->toBe($this->laeufer->verwaltet_von)
        ->and($sponsoring->projects->pluck('id')->all())->toBe([$projekt->id])
        ->and(User::count())->toBe($users)
        ->and(Sponsor::first()->users->pluck('id')->all())->toBe([$this->laeufer->verwaltet_von]);
});

it('erkennt bekannte Sponsoren an der E-Mail-Adresse', function () {
    Sponsor::factory()->create(['email' => 'erika@example.org']);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular())->assertRedirect();

    expect(Sponsor::count())->toBe(1);
});

it('erlaubt Firmen als Sponsor', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['anrede' => 'Firma', 'vorname' => '', 'nachname' => '', 'firmenname' => 'Bäckerei Krume', 'festBetrag' => 50, 'rundenBetrag' => '']))
        ->assertSessionHasNoErrors();

    expect(Sponsor::first()->firmenname)->toBe('Bäckerei Krume');
});

it('verlangt einen Betrag', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['rundenBetrag' => '']))
        ->assertSessionHasErrors('festBetrag');
});

it('blockiert Bots über das Honeypot-Feld', function () {
    config(['honeypot.randomize_name_field_name' => false]);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['my_name' => 'bot']));

    expect(Sponsoring::count())->toBe(0);
});

it('nimmt nach dem Lauf keine Zusagen mehr an', function () {
    $this->event->update(['datum' => now()->subDays(2)]);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular())->assertSessionHas('type', 'danger');

    expect(Sponsoring::count())->toBe(0);
});

it('liefert 404 für unbekannte Läufer', function () {
    $this->get(route('public.sponsor.show', 'gibt-es-nicht'))->assertNotFound();
});
