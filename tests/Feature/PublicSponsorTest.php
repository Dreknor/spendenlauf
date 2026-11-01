<?php

use App\Mail\SpendenzusageBestaetigen;
use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Spendenzusage;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Honeypot\EncryptedTime;

beforeEach(function () {
    Mail::fake();
    $this->event = aktivesEvent();
    $this->laeufer = Laeufer::factory()->create(['event_id' => $this->event->id]);
});

function sponsorFormular(array $extra = []): array
{
    return array_merge([
        'anrede' => 'Frau', 'vorname' => 'Erika', 'nachname' => 'Mustermann', 'email' => 'erika@example.org',
        'strasse' => 'Hauptstr. 1', 'plz' => '01445', 'ort' => 'Radebeul', 'rundenBetrag' => '1.50', 'datenschutz' => '1',
        // Honeypot-Felder wie im echten Formular
        'my_name_test' => '', 'valid_from' => (string) EncryptedTime::create(now()->subMinute()),
    ], $extra);
}

/** Zusage über das Formular abgeben und per Link aus der Mail bestätigen. */
function zusageAbgebenUndBestaetigen($test, array $extra = [])
{
    $test->post(route('public.sponsor.store', $test->laeufer->uuid), sponsorFormular($extra))->assertSessionHasNoErrors();

    return $test->post(Spendenzusage::latest('id')->first()->bestaetigungsLink());
}

it('legt Sponsor und Spende erst nach Bestätigung per E-Mail an', function () {
    $users = User::count();
    $projekt = Projects::factory()->create();
    $this->event->projects()->attach($projekt);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular())->assertRedirect()->assertSessionHas('type', 'success');

    Mail::assertQueued(SpendenzusageBestaetigen::class, fn ($m) => $m->hasTo('erika@example.org'));
    expect(Sponsor::count())->toBe(0)->and(Sponsoring::count())->toBe(0);

    // Öffnen des Links bestätigt noch nicht (Mail-Scanner rufen Links automatisch auf)
    $link = Spendenzusage::sole()->bestaetigungsLink();
    $this->get($link)->assertOk()->assertSee('Spende verbindlich zusagen');
    expect(Sponsoring::count())->toBe(0);

    $this->post($link)->assertRedirect(route('public.sponsor.show', $this->laeufer->uuid));

    $sponsoring = Sponsoring::sole();
    expect(Sponsor::count())->toBe(1)
        ->and($sponsoring->sponsorable_id)->toBe($this->laeufer->id)
        ->and((float) $sponsoring->rundenBetrag)->toBe(1.5)
        ->and($sponsoring->verwaltet_von)->toBe($this->laeufer->verwaltet_von)
        ->and($sponsoring->projects->pluck('id')->all())->toBe([$projekt->id])
        ->and(User::count())->toBe($users)
        ->and(Sponsor::first()->users->pluck('id')->all())->toBe([$this->laeufer->verwaltet_von])
        ->and(Spendenzusage::count())->toBe(0);

    // Ein zweiter Klick legt nichts doppelt an
    $this->post($link)->assertOk()->assertSee('Link nicht mehr gültig');
    expect(Sponsoring::count())->toBe(1);
});

it('rendert die Bestätigungsmail mit Beträgen und Link', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['festBetrag' => 20]));
    $zusage = Spendenzusage::sole();

    (new SpendenzusageBestaetigen($zusage))
        ->assertSeeInHtml('Sehr geehrte Frau Mustermann')
        ->assertSeeInHtml('1,50 €')
        ->assertSeeInHtml('20,00 €')
        ->assertSeeInHtml('spende/bestaetigen/'.$zusage->id);
});

it('lehnt manipulierte und abgelaufene Bestätigungslinks ab', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular());
    $link = Spendenzusage::sole()->bestaetigungsLink();

    $this->post(preg_replace('/signature=\w/', 'signature=0', $link))->assertSee('Link nicht mehr gültig');

    $this->travel(Spendenzusage::GUELTIG_TAGE + 1)->days();
    $this->get($link)->assertSee('Link nicht mehr gültig');
    $this->post($link)->assertSee('Link nicht mehr gültig');

    expect(Sponsoring::count())->toBe(0);
});

it('löscht unbestätigte Zusagen nach Ablauf', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular());

    $this->travel(Spendenzusage::GUELTIG_TAGE + 1)->days();
    $this->artisan('model:prune', ['--model' => [Spendenzusage::class]])->assertSuccessful();

    expect(Spendenzusage::count())->toBe(0);
});

it('erkennt bekannte Sponsoren an der E-Mail-Adresse', function () {
    Sponsor::factory()->create(['email' => 'erika@example.org']);

    zusageAbgebenUndBestaetigen($this);

    expect(Sponsor::count())->toBe(1)->and(Sponsoring::count())->toBe(1);
});

it('erlaubt Firmen als Sponsor', function () {
    zusageAbgebenUndBestaetigen($this, ['anrede' => 'Firma', 'vorname' => '', 'nachname' => '', 'firmenname' => 'Bäckerei Krume', 'festBetrag' => 50, 'rundenBetrag' => '']);

    expect(Sponsor::first()->firmenname)->toBe('Bäckerei Krume');
});

it('verlangt einen Betrag', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['rundenBetrag' => '']))
        ->assertSessionHasErrors('festBetrag');
});

it('begrenzt die Beträge im öffentlichen Formular', function () {
    config(['spendenlauf.oeffentlich.max_betrag' => 1000, 'spendenlauf.oeffentlich.max_runde' => 25]);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['festBetrag' => 100000, 'rundenBetrag' => 30, 'maxBetrag' => 5000]))
        ->assertSessionHasErrors(['festBetrag', 'rundenBetrag', 'maxBetrag']);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['festBetrag' => 1000, 'rundenBetrag' => 25]))
        ->assertSessionHasNoErrors();

    expect(Spendenzusage::count())->toBe(1);
});

it('blockiert Bots über das Honeypot-Feld', function () {
    config(['honeypot.randomize_name_field_name' => false]);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['my_name' => 'bot']));

    expect(Spendenzusage::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('blockiert Bots, die die Honeypot-Felder weglassen', function () {
    $formular = sponsorFormular();
    unset($formular['my_name_test'], $formular['valid_from']);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), $formular)->assertSessionHasErrors('valid_from');

    expect(Spendenzusage::count())->toBe(0);
});

it('nimmt nach dem Lauf keine Zusagen mehr an', function () {
    $this->event->update(['datum' => now()->subDays(2)]);

    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular())->assertSessionHas('type', 'danger');

    expect(Spendenzusage::count())->toBe(0);
});

it('bestätigt keine Zusagen mehr, wenn der Lauf vorbei ist', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular());
    $this->event->update(['datum' => now()->subDays(2)]);

    $this->post(Spendenzusage::sole()->bestaetigungsLink())->assertSee('Spendenlauf vorbei');

    expect(Sponsoring::count())->toBe(0);
});

it('liefert 404 für unbekannte Läufer', function () {
    $this->get(route('public.sponsor.show', 'gibt-es-nicht'))->assertNotFound();
});

it('verlangt die vollständige Anschrift', function () {
    $this->post(route('public.sponsor.store', $this->laeufer->uuid), sponsorFormular(['strasse' => '', 'plz' => '', 'ort' => '']))
        ->assertSessionHasErrors(['strasse', 'plz', 'ort']);
});
