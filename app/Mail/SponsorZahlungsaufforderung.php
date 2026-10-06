<?php

namespace App\Mail;

use App\Enums\MailTyp;
use App\Models\Event;
use App\Models\Sponsor;
use App\Services\SponsorAbrechnung;
use App\Services\Statistik;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Dank + Zahlungsaufforderung bzw. Erinnerung an einen Sponsor.
 * Die Beträge werden erst beim Versand berechnet.
 */
class SponsorZahlungsaufforderung extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Sponsor $sponsor, public Event $event, public MailTyp $typ) {}

    public function envelope(): Envelope
    {
        $betreff = $this->typ === MailTyp::Erinnerung
            ? 'Erinnerung: Ihre Spende zum '.$this->event->name
            : 'Vielen Dank für Ihre Unterstützung beim '.$this->event->name;

        return new Envelope(
            from: new Address($this->event->absenderAdresse(), $this->event->absenderName()),
            subject: $betreff,
        );
    }

    public function content(): Content
    {
        $statistik = Statistik::fuer($this->event)->uebersicht();

        return new Content(markdown: 'emails.sponsoren.zahlung', with: [
            'abrechnung' => SponsorAbrechnung::fuer($this->sponsor, $this->event),
            'erinnerung' => $this->typ === MailTyp::Erinnerung,
            'gesamtSumme' => $statistik['brutto'],
            'anzahlLaeufer' => $statistik['teilnehmer'],
        ]);
    }
}
