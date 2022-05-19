<?php

namespace App\Repositories;

use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Sponsoring;
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

    public function spendensumme(){
        $sponsoring = Sponsoring::all();

        return $sponsoring->sum('spende');
    }
    public function spendensummeProjects(){
        $sponsorings = Sponsoring::all();

        $Summe = [];

        foreach ($sponsorings as $sponsoring){
            $sponsoringProjectsArray = $sponsoring->SpendeProjekt;

            foreach ($sponsoringProjectsArray as $key => $value){
                if (array_key_exists($key, $Summe)){
                    $Summe[$key] += $value;
                } else {
                    $Summe[$key] = $value;
                }

            }

        }

        return $Summe;
    }
}
