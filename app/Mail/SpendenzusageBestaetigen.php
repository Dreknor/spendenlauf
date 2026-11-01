<?php

namespace App\Mail;

use App\Models\Spendenzusage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Bitte um Bestätigung einer Spendenzusage aus dem öffentlichen Sponsor-Link (Double-Opt-in). */
class SpendenzusageBestaetigen extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Spendenzusage $zusage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->zusage->event->absenderAdresse(), $this->zusage->event->absenderName()),
            subject: 'Bitte bestätigen Sie Ihre Spendenzusage zum '.$this->zusage->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.sponsoren.zusage-bestaetigen', with: [
            'daten' => $this->zusage->daten,
            'laeufer' => $this->zusage->laeufer,
            'event' => $this->zusage->event,
            'link' => $this->zusage->bestaetigungsLink(),
        ]);
    }
}
