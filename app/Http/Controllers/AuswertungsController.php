<?php

namespace App\Http\Controllers;

use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use App\Model\Teams;

class AuswertungsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:show auswertung');
    }

    public function index()
    {
        $laeufers = Laeufer::all();
        $laeufers->load('sponsorings', 'sponsorings', 'sponsorings.sponsorable');
        $teams = Teams::with(['laeufer', 'sponsorings', 'sponsorings.sponsorable', 'sponsorings.projects'])->get();
        $sponsoren = Sponsor::all();

        $sponsoring = Sponsoring::all();
        $sponsoring->load('sponsorable', 'sponsor', 'projects');

        return view('auswertung.index', [
            'Laeufer'=>  $laeufers->count(),
            'Teams'=> $teams->count(),
            'Sponsoren'=> $sponsoren->count(),
            'Spenden'   => $sponsoring->sum('spende'),
            'laeuferRunden'   => $laeufers,
            'teamsRunden'   => $teams,
        ]);
    }
}
