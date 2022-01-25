<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTeamRequest;
use App\Model\Teams;
use Illuminate\Http\Request;

class TeamsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (auth()->user()->can('edit teams')) {
            $teams = Teams::query()->orderBy('name')->paginate(30);
        } else {
            $teams = auth()->user()->teams()->paginate(10);
        }

        $teams->load(['laeufer', 'sponsorings', 'sponsorings.sponsorable', 'besitzer']);

        return view('teams.index', [
            'teams' => $teams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('teams.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTeamRequest $request)
    {
        $team = new Teams();
        $team->fill($request->all());
        $team->verwaltet_von = auth()->user()->id;

        $team->save();

        return redirect(url('teams'))->with([
            'type'   => 'success',
            'Meldung'    =>__('Team wurde erstellt.'),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Teams  $teams
     * @return \Illuminate\Http\Response
     */
    public function show(Teams $team)
    {
        return $this->edit($team);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Teams  $teams
     * @return \Illuminate\Http\Response
     */
    public function edit(Teams $team)
    {
        if (auth()->user()->can('edit teams') or $team->verwaltet_von == auth()->user()->id) {
            $team->load(['sponsorings', 'sponsorings.sponsor', 'sponsorings.sponsorable', 'sponsorings.projects', 'sponsorings.sponsorable']);

            return view('teams.edit', [
                'team'  => $team,
            ]);
        }

        return redirect()->back()->with([
            'type'  => 'danger',
            'Meldung'   => __('Berechtigung fehlt'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Teams  $teams
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Teams $team)
    {
        if (! auth()->user()->can('edit teams') and $team->verwaltet_von != auth()->user()->id) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        $team->update([
            'name'  => $request->name,
            'open'   => $request->open,
        ]);

        return redirect(url('teams'))->with([
            'type'  => 'success',
            'Meldung'  => __('Änderungen gespeichert'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Teams  $teams
     * @return \Illuminate\Http\Response
     */
    public function destroy(Teams $teams)
    {
        //
    }
}
