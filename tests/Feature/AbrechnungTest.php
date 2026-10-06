<?php

use App\Enums\MailTyp;
use App\Enums\ZahlungsStatus;
use App\Mail\SponsorZahlungsaufforderung;
use App\Models\Laeufer;
use App\Models\Sponsoring;
use App\Models\SponsorMail;
use App\Models\Zahlung;
use App\Services\SpendenRechner;
use App\Services\SponsorAbrechnung;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->event = aktivesEvent(['datum' => now()->subDays(2)->toDateString(), 'paypal_me_name' => 'Foerderverein']);
    $this->laeufer = Laeufer::factory()->runden(20)->create(['event_id' => $this->event->id]);
    $this->sponsoring = Sponsoring::factory()->fuer($this->laeufer)->create(['rundenBetrag' => 1, 'festBetrag' => 5]);
    $this->sponsor = $this->sponsoring->sponsor;
    $this->kasse = admin();
});

it('erstellt Aufstellung, Verwendungszweck und PayPal-Link', function () {
    $a = SponsorAbrechnung::fuer($this->sponsor, $this->event);

    expect($a->konto->sollCent)->toBe(2500)
        ->and($a->status())->toBe(ZahlungsStatus::Offen)
        ->and($a->verwendungszweck())->toBe('Spendenlauf '.$this->event->jahr().' Nr. '.$this->sponsor->id)
        ->and($a->paypalLink())->toBe('https://paypal.me/Foerderverein/25.00EUR')
        ->and($a->positionen->first()['runden'])->toBe(20);
});

it('erfasst Zahlungen und aktualisiert den Status', function () {
    $this->actingAs($this->kasse)->post(route('zahlungen.store', $this->sponsor), [
        'betrag' => '10', 'bezahlt_am' => now()->toDateString(), 'art' => 'ueberweisung',
    ])->assertSessionHasNoErrors();
    expect(SpendenRechner::fuer($this->event)->sponsorKonto($this->sponsor)->status())->toBe(ZahlungsStatus::Teilweise);

    $this->actingAs($this->kasse)->post(route('zahlungen.store', $this->sponsor), [
        'betrag' => '15', 'bezahlt_am' => now()->toDateString(), 'art' => 'paypal',
    ]);
    expect(SpendenRechner::fuer($this->event)->sponsorKonto($this->sponsor)->status())->toBe(ZahlungsStatus::Bezahlt);
});

it('versendet Zahlungsaufforderungen nur einmal und protokolliert sie', function () {
    Mail::fake();

    $this->actingAs($this->kasse)->post(route('abrechnung.mails.senden'), ['typ' => 'aufforderung', 'sponsoren' => [$this->sponsor->id]])->assertRedirect();

    Mail::assertQueued(SponsorZahlungsaufforderung::class, fn ($m) => $m->hasTo($this->sponsor->email) && $m->typ === MailTyp::Aufforderung);
    expect(SponsorMail::count())->toBe(1);

    // Vorschau zeigt den Sponsor jetzt nicht mehr als Empfänger einer Aufforderung
    $this->actingAs($this->kasse)->get(route('abrechnung.mails', ['typ' => 'aufforderung']))->assertSee('Keine passenden Empfänger');
});

it('rendert die Zahlungsaufforderung mit Betrag, Bankdaten und Links', function () {
    $mail = new SponsorZahlungsaufforderung($this->sponsor, $this->event, MailTyp::Aufforderung);

    $mail->assertSeeInHtml('25,00 €')
        ->assertSeeInHtml('DE40 8505 5000 0500 0279 60')
        ->assertSeeInHtml('Spendenlauf '.$this->event->jahr().' Nr. '.$this->sponsor->id)
        ->assertSeeInHtml('paypal.me/Foerderverein/25.00EUR')
        ->assertSeeInHtml(route('public.sponsor.konto', $this->sponsor->uuid));
});

it('bietet den vereinfachten Nachweis erst nach vollständiger Zahlung', function () {
    $this->get(route('public.sponsor.nachweis', $this->sponsor->uuid))->assertNotFound();

    Zahlung::factory()->create(['event_id' => $this->event->id, 'sponsor_id' => $this->sponsor->id, 'betrag' => 25]);
    SpendenRechner::vergessen();

    $this->get(route('public.sponsor.nachweis', $this->sponsor->uuid))->assertOk()->assertHeader('content-type', 'application/pdf');
});
