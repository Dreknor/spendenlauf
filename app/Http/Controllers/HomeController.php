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

        return view('home', [
            'Laeufer'=>  $repository->anzahlLauefer(),
            'Teams'=> $repository->anzahlTeams(),
            'Sponsoren'=> $repository->anzahlSponsoren(),
            'Spenden'   => $repository->spendensumme('spende'),
        ]);
    }
}
