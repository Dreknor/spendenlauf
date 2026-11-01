<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamRequest;
use App\Models\Teams;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $teams = Teams::query()
            ->withCount('laeufer')
            ->withSum('laeufer', 'runden')
            ->with('besitzer')
            ->unless($user->can('edit teams'), fn ($q) => $q->where(fn ($q) => $q->where('verwaltet_von', $user->id)
                ->orWhereHas('laeufer', fn ($l) => $l->verwaltetVon($user))))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('teams.index', [
            'teams' => $teams,
            'bilanz' => SpendenRechner::fuer($this->event())->bilanz(),
        ]);
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(TeamRequest $request)
    {
        $team = new Teams(['name' => $request->input('name'), 'open' => $request->boolean('open')]);
        $team->verwaltet_von = $request->user()->id;
        $team->save();

        return $this->erfolg("Team „{$team->name}“ wurde erstellt.", route('teams.edit', $team));
    }

    public function show(Teams $team)
    {
        return redirect()->route('teams.edit', $team);
    }

    public function edit(Teams $team)
    {
        $this->authorize('view', $team);

        $team->load(['laeufer' => fn ($q) => $q->orderBy('nachname'), 'laeufer.schulklasse', 'sponsorings.sponsor.users', 'besitzer']);

        return view('teams.edit', [
            'team' => $team,
            'bilanz' => SpendenRechner::fuer($this->event())->bilanz(),
        ]);
    }

    public function update(TeamRequest $request, Teams $team)
    {
        $this->authorize('update', $team);

        $team->update(['name' => $request->input('name'), 'open' => $request->boolean('open')]);

        return $this->erfolg('Änderungen gespeichert.');
    }

    public function destroy(Request $request, Teams $team)
    {
        $this->authorize('delete', $team);

        if ($team->laeufer()->exists()) {
            return $this->fehler('Das Team kann nicht gelöscht werden, da es noch Läufer enthält.');
        }

        if ($team->sponsorings()->exists()) {
            return $this->fehler('Das Team kann nicht gelöscht werden, da es noch Sponsorings hat.');
        }

        Log::info('Team gelöscht', ['team' => $team->name, 'id' => $team->id, 'user' => $request->user()->name]);
        $team->delete();

        return $this->erfolg('Team gelöscht.', route('teams.index'));
    }
}
