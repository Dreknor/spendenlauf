<?php

namespace App\Http\Controllers;

use App\Http\Requests\addTeamRequest;
use App\Http\Requests\CreateLaeuferRequest;
use App\Mail\AddLaeuferToTeam;
use App\Model\Laeufer;
use App\Model\Startnummer;
use App\Model\Teams;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LaeuferController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (auth()->user()->can('edit laeufer')) {
            $laeufer = Laeufer::query()->orderBy('nachname')->paginate(40);
        } else {
            $laeufer = auth()->user()->laeufer()->orderBy('nachname')->paginate(20);
        }

        $laeufer->load(['team', 'besitzer']);

        return view('laeufer.index', [
            'laeufer'   => $laeufer,
        ]);
    }

    public function show(Laeufer $laeufer)
    {
        return $this->edit($laeufer);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laeufer.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateLaeuferRequest $request)
    {
        $Input = $request->all();

        $Startnummer = Startnummer::query()->first();
        $Laeufer = Laeufer::firstOrCreate([
            'vorname'    =>  $Input['vorname'],
            'nachname'    =>  $Input['nachname'],
            'geburtsdatum'    => $Input['geburtsdatum'],
        ], [
            'geschlecht'    => $Input['geschlecht'],
            'startnummer'   => $Startnummer->startnummer,
            'email'         => $Input['email'],
            'verwaltet_von' => auth()->user()->id,
        ]);

        if ($Laeufer->wasRecentlyCreated) {
            $Startnummer->delete();

            return redirect(url('/laeufer'))->with([
                'type'   => 'success',
                'Meldung'    => 'Läufer wurde angemeldet.',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Laeufer  $laeufer
     * @return \Illuminate\Http\Response
     */
    public function edit(Laeufer $laeufer)
    {
        return view('laeufer.edit', [
            'Laeufer'=> $laeufer->load(['team', 'sponsorings', 'sponsorings.sponsor', 'sponsorings.projects', 'sponsorings.sponsorable']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Laeufer  $laeufer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Laeufer $laeufer)
    {
        $laeufer->update($request->all());

        return redirect(url('laeufer'))->with([
            'type'   => 'success',
            'Meldung'    => 'Daten wurden geändert.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Laeufer  $laeufer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Laeufer $laeufer)
    {
        //
    }

    public function addTeam(Laeufer $laeufer)
    {
        if (! auth()->user()->can('edit laeufer') and $laeufer->verwaltet_von != auth()->user()->id) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        if (auth()->user()->can('edit teams')) {
            $teams = Teams::query()->orderBy('name')->get();
        } else {
            $teams = Teams::query()
                ->where('verwaltet_von', auth()->user()->id)
                ->orWhere('open', 1)
                ->orderBy('open', 'desc')
                ->orderBy('name')
                ->get();
        }

        return view('laeufer.addTeam', [
            'laeufer'   => $laeufer,
            'teams' => $teams->load('laeufer'),
        ]);
    }

    public function storeTeam(Laeufer $laeufer, addTeamRequest $request)
    {
        $Team = Teams::find($request->team_id);
        $laeufer->team_id = $Team->id;
        $laeufer->save();

        if ($Team->verwaltet_von != auth()->user()->id) {
            Mail::to($Team->besitzer->email)->bcc('daniel.roehrich@esz-radebeul.de')->queue(new AddLaeuferToTeam($Team->besitzer->name, $laeufer->name, $Team->name));
        }

        return redirect(url('laeufer'))->with([
            'type'   => 'success',
            'Meldung'    => __('Läufer wurde dem Team hinzugefügt.'),
        ]);
    }

    public function removeTeam(Laeufer $laeufer)
    {
        $laeufer->team_id = null;
        $laeufer->save();

        return redirect()->back()->with([
            'type'   => 'success',
            'Meldung'    => __('Läufer wurde aus Team entfernt.'),
        ]);
    }
}
