<?php

namespace App\Http\Controllers;

use App\Model\Laeufer;
use App\Model\Teams;
use App\Repositories\SpendenlaufRepository;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function stats(){

        $repository = new SpendenlaufRepository();

        $teams = Teams::with('laeufer')->get();
        $bestesTeam = 0;

        foreach ($teams as $team){
            if ($bestesTeam < $team->runden){
                $bestesTeam = $team->runden;
            }
        }

        return response()->view('stats', [
            'Laeufer'=>  $repository->anzahlLauefer(),
            'Teams'=> $repository->anzahlTeams(),
            'Sponsoren'=> $repository->anzahlSponsoren(),
            'Spenden'   => $repository->spendensumme('spende'),
            'gesamtRunden'    => Laeufer::query()->sum('runden'),
            'aktiveLaeufer' => Laeufer::where('runden', '>', 0)->count(),
            'besterLaeufer' => Laeufer::orderByDesc('runden')->first(),
            'bestesTeam'    => $bestesTeam
        ])
            ->header('Content-Security-Policy', config('cors.Content-Security-Policy'))
            ->header('X-Frame-Options', config('cors.X-Frame-Options'));
    }
}
