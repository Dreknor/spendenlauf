<?php

namespace App\Repositories;

use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use App\Model\Teams;
use Illuminate\Support\Facades\Cache;

class SpendenlaufRepository
{
    public function anzahlLauefer()
    {
        return Laeufer::count();
    }

    public function anzahlSponsoren()
    {
        return Sponsor::count();
    }

    public function anzahlTeams()
    {
        return Teams::count();
    }

    public function spendensumme(){
        return Cache::remember('sponsorings', 60*10, function (){
            $sponsoring = Sponsoring::with('sponsorable')->get();
            return $sponsoring->sum('spende');
        });
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
