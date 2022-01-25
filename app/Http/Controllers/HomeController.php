<?php

namespace App\Http\Controllers;

use App\Model\Sponsoring;
use App\Repositories\SpendenlaufRepository;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $repository = new SpendenlaufRepository();

        $sponsoring = Sponsoring::all();
        $sponsoring->load('sponsorable', 'sponsor', 'projects');

        return view('home', [
            'Laeufer'=>  $repository->anzahlLauefer(),
            'Teams'=> $repository->anzahlTeams(),
            'Sponsoren'=> $repository->anzahlSponsoren(),
            'Spenden'   => $sponsoring->sum('spende'),
        ]);
    }
}
