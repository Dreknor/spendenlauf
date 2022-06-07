<?php

namespace App\Http\Controllers;

use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Teams;
use App\Repositories\SpendenlaufRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function stats($full = false){
        $repository = new SpendenlaufRepository();

        $teams = Cache::remember('teams', 60000, function (){
            return Teams::with(['laeufer', 'laeufer.sponsorings'])->get()->sortByDesc('runden');
        });

        $laeufer = Cache::remember('laeufer', 60000, function (){
            return Laeufer::with('sponsorings')->get()->sortByDesc('runden');
        });


        $spendensumme = Cache::remember('spendensumme', 60000, function () use ($repository){
            return $repository->spendensumme();
        });

        $sponsoren = Cache::remember('sponsoren', 60000, function () use ($repository){
            return Sponsor::count();
        });


        return response()->view('stats', [
            'Laeufer'=>  $laeufer->count(),
            'Teams'=> $teams->count(),
            'Sponsoren'=> $sponsoren,
            'Spenden'   => $spendensumme,
            'gesamtRunden'    => $laeufer->sum('runden'),
            'aktiveLaeufer' => $laeufer->where('runden', '>', 0)->count(),
            'besterLaeufer' => $laeufer->first(),
            'bestesTeam'    => $teams->first()->name,
            'laeufers'      => $laeufer,
            'teams' => $teams
        ])
            ->header('Content-Security-Policy', config('cors.Content-Security-Policy'))
            ->header('X-Frame-Options', config('cors.X-Frame-Options'));
    }

}
