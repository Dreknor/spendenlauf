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
use Barryvdh\DomPDF\Facade\Pdf;

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
            $laeufer = Laeufer::query()->orderBy('nachname')->get();
        } else {
            $laeufer = auth()->user()->laeufer()->orderBy('nachname')->get();
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
     * @return
     */
    public function create()
    {
        if (!auth()->user()->can('edit laeufer|edit teams')) {
            $teams = auth()->user()->teams()->orderBy('name')->get();
        } else {
            $teams = Teams::query()->orderBy('name')->get();
        }

        return view('laeufer.create',
        [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateLaeuferRequest $request)
    {
        $Input = $request->validated();

        $Startnummer = Startnummer::orderBy('startnummer')->first();
        $Laeufer = Laeufer::firstOrCreate([
            'vorname'    =>  $Input['vorname'],
            'nachname'    =>  $Input['nachname'],
            'geburtsdatum'    => $Input['geburtsdatum'],
        ], [
            'geschlecht'    => $request->geschlecht ?? null,
            'startnummer'   => $Startnummer->startnummer,
            'email'         => $request->email ?? null,
            'verwaltet_von' => auth()->id(),
        ]);

        if ($Laeufer->wasRecentlyCreated) {
            $Startnummer->delete();

            if ($request->team_id) {
                $Laeufer->team_id =$request->team_id;
                $Laeufer->save();
            } elseif ($request->team_name) {
                $Team = Teams::firstOrCreate([
                    'name'  => $request->team_name,
                    'verwaltet_von' => auth()->id(),

                ]);
                $Laeufer->team_id = $Team->id;
                $Laeufer->save();
            }



            return redirect(url('/laeufer'))->with([
                'type'   => 'success',
                'Meldung'    => 'Läufer wurde angemeldet. Startnummer: '.$Laeufer->startnummer,
            ]);
        }

        return redirect(url('/laeufer'))->with([
            'type'   => 'warning',
            'Meldung'    => 'Läufer bereits vorhanden. Startnummer: '.$Laeufer->startnummer,
        ]);
    }

    /**
     *
     *
     * @param  \App\Model\Laeufer  $laeufer
     * @return \Illuminate\Http\Response
     */
    public function bescheinigung(Laeufer $laeufer)
    {
        if ($laeufer->verwaltet_von != auth()->id() and !auth()->user()->can('edit laeufer')){
            return back()->with([
                'type'   => 'danger',
                'Meldung'    => 'Berechtigung fehlt.',
            ]);
        }

        $pdf = PDF::loadView('laeufer.bescheinigung', [
            'laeufer' => $laeufer
        ]);
        return $pdf->download('Bescheinigung.pdf');

    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Laeufer  $laeufer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit(Laeufer $laeufer)
    {
        if ($laeufer->verwaltet_von != auth()->id() and !auth()->user()->can('edit laeufer')){
            return back()->with([
                'type'   => 'danger',
                'Meldung'    => 'Berechtigung fehlt.',
            ]);
        }

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
