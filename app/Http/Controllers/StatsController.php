<?php

namespace App\Http\Controllers;

use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use App\Model\Teams;
use App\Repositories\SpendenlaufRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function stats($full = false){
        $repository = new SpendenlaufRepository();

        $teams = Cache::remember('teams', 600, function (){
            return Teams::with(['laeufer', 'laeufer.sponsorings'])->get()->sortByDesc('runden');
        });

        $laeufer = Cache::remember('laeufer', 600, function (){
            return Laeufer::with('sponsorings')->get()->sortByDesc('runden');
        });


        $spendensumme = Cache::remember('spendensumme', 600, function () use ($repository){
            return $repository->spendensumme();
        });

        $sponsoren = Cache::remember('sponsoren', 600, function () use ($repository){
            return Sponsor::count();
        });

        $runden_durchschnitt = Cache::remember('runden_durchschnitt', 600, function () use ($repository){
            return round(Sponsoring::where('rundenBetrag', '>', 0)->sum('rundenBetrag')/Sponsoring::count(),2);
        });


        return response()->view('stats', [
            'Laeufer'=>  $laeufer->count(),
            'Teams'=> $teams->count(),
            'Sponsoren'=> $sponsoren,
            'Spenden'   => $spendensumme,
            'gesamtRunden'    => $laeufer->sum('runden'),
            'aktiveLaeufer' => $laeufer->where('runden', '>', 0)->count(),
            'besterLaeufer' => $laeufer->first(),
            'bestesTeam'    => $teams->first()?->name,
            'laeufers'      => $laeufer,
            'teams' => $teams,
            'runden_durchschnitt_sponsoring' => $runden_durchschnitt
        ])
            ->header('Content-Security-Policy', config('cors.Content-Security-Policy'))
            ->header('X-Frame-Options', config('cors.X-Frame-Options'));
    }

}
