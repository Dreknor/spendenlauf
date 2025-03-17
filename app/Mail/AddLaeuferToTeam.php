<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AddLaeuferToTeam extends Mailable
{
    use Queueable, SerializesModels;

    public $name;

    public $Laeufer;

    public $Team;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $Laeufer, $Team)
    {
        $this->Laeufer = $Laeufer;
        $this->name = $name;
        $this->Team = $Team;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('neuer Läufer im Team'))->view('emails.addLaeuferToTeam');
    }
}
