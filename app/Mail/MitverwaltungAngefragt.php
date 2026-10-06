<?php

namespace App\Mail;

use App\Models\MitverwaltungAnfrage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MitverwaltungAngefragt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MitverwaltungAnfrage $anfrage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Anfrage zur Mitverwaltung von '.$this->anfrage->laeufer->vorname);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.mitverwaltung-angefragt');
    }
}
