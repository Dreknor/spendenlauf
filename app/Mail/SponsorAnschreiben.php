<?php

namespace App\Mail;

use App\Model\Sponsor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SponsorAnschreiben extends Mailable
{
    use Queueable, SerializesModels;

    public $sponsor;

    public $countLaeufer;

    public $spendensumme;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Sponsor $sponsor, Int $countLaeufer, Float $spendensumme)
    {
        $this->sponsor = $sponsor;
        $this->countLaeufer = $countLaeufer;
        $this->spendensumme = $spendensumme;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.sponsoren.anschreiben', [
            'sponsor'   => $this->sponsor,
        ])->from('info@esz-radebeul.de', 'Evangelisches Schulzentrum Radebeul')->subject('Radebeuler Spendenlauf');
    }
}
