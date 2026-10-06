<?php

namespace App\Mail;

use App\Models\MitverwaltungAnfrage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MitverwaltungEntschieden extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MitverwaltungAnfrage $anfrage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ihre Anfrage zu '.$this->anfrage->laeufer->vorname.' wurde '.$this->anfrage->status);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.mitverwaltung-entschieden');
    }
}
