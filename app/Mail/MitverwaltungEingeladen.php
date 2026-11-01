<?php

namespace App\Mail;

use App\Models\MitverwaltungEinladung;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MitverwaltungEingeladen extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MitverwaltungEinladung $einladung) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Einladung: '.$this->einladung->laeufer->vorname.' beim Spendenlauf mitverwalten');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.mitverwaltung-eingeladen');
    }
}
