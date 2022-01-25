<?php

namespace App\Repositories;

use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Teams;

class SpendenlaufRepository
{
    public function anzahlLauefer()
    {
        return Laeufer::all()->count();
    }

    public function anzahlSponsoren()
    {
        return Sponsor::all()->count();
    }

    public function anzahlTeams()
    {
        return Teams::all()->count();
    }
}
