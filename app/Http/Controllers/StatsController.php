<?php

namespace App\Http\Controllers;

use App\Repositories\SpendenlaufRepository;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function stats(){

        $repository = new SpendenlaufRepository();

        return response()->view('stats', [
            'Laeufer'=>  $repository->anzahlLauefer(),
            'Teams'=> $repository->anzahlTeams(),
            'Sponsoren'=> $repository->anzahlSponsoren(),
            'Spenden'   => $repository->spendensumme('spende'),
        ])
            ->header('Content-Security-Policy', config('cors.Content-Security-Policy'))
            ->header('X-Frame-Options', config('cors.X-Frame-Options'));
    }
}
