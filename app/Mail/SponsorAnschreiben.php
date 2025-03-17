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
    private int $sponsoring_projects;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Sponsor $sponsor, Int $countLaeufer, Float $spendensumme, Int $sponsoring_projects)
    {
        $this->sponsor = $sponsor;
        $this->countLaeufer = $countLaeufer;
        $this->spendensumme = $spendensumme;
        $this->sponsoring_projects = $sponsoring_projects;
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
            'sponsoring_projects' => $this->sponsoring_projects
        ])->from('info@esz-radebeul.de', 'Evangelisches Schulzentrum Radebeul')->subject('Radebeuler Spendenlauf');
    }
}
