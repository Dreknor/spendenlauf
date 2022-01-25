<?php

namespace App\Http\Controllers;

use App\Model\Laeufer;
use App\Model\Projects;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use App\Model\Teams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class SponsoringController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (auth()->user()->can('edit sponsorings')) {
            $sponsorings = Sponsoring::orderByDesc('created_at')->get();
        } else {
            $sponsorings = auth()->user()->sponsorings->sortByDesc('created_at')->paginate(20);
        }

        $sponsorings->load('sponsorable', 'sponsor', 'projects');

        return view('sponsorings.index', [
            'sponsorings'   => $sponsorings,
            'summe'         => $sponsorings->sum('spende'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (auth()->user()->can('edit laeufer')) {
            $laeufer = Laeufer::orderBy('nachname')->get();
        } else {
            $laeufer = auth()->user()->laeufer()->orderBy('nachname')->get();
        }

        if (auth()->user()->can('edit sponsoren')) {
            $sponsors = Sponsor::orderBy('nachname')->get();
        } else {
            $sponsors = auth()->user()->sponsoren()->orderBy('nachname')->get();
        }

        if (auth()->user()->can('edit teams')) {
            $teams = Teams::orderBy('name')->get();
        } else {
            //$teams = auth()->user()->teams()->orderBy('name')->get();
            $teams = Teams::query()
                ->where('verwaltet_von', auth()->user()->id)
                ->orWhere('open', 1)
                ->orderByDesc('open')
                ->orderBy('name')
                ->get();
        }
        $projects = Projects::all();

        return view('sponsorings.create', [
            'laeufers'  => $laeufer->sortBy('nachname'),
            'sponsors'  => $sponsors->sortBy('nachname'),
            'projects'  => $projects,
            'teams'     => $teams->sortBy('name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $sponsoring = new Sponsoring();
        $sponsoring->fill($request->all());
        $sponsoring->verwaltet_von = auth()->user()->id;
        $sponsoring->sponsor_id = $request->sponsor;

        if ($request->type == 'Team') {
            $Team = Teams::find($request->team);

            $sponsoring->sponsorable_id = $request->team;
            $sponsoring->sponsorable_type = get_class($Team);
        } else {
            $Laeufer = Laeufer::find($request->laeufer);

            $sponsoring->sponsorable_id = $request->laeufer;
            $sponsoring->sponsorable_type = get_class($Laeufer);
        }

        $sponsoring->save();

        if (count($request->projects) > 0) {
            $sponsoring->projects()->sync($request->projects);
        } else {
            $sponsoring->projects()->sync(Projects::all());
        }

        return redirect(url('sponsorings'))->with([
            'type'  => 'success',
            'Meldung'   => __('Spende wurde erstellt'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Sponsoring  $sponsoring
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sponsoring $sponsoring)
    {
        if ($sponsoring->verwaltet_von == auth()->user()->id or auth()->user()->can('edit sponsorings')) {
            $sponsoring->projects()->detach();
            $sponsoring->delete();

            return redirect()->back()->with([
                'type'  => 'success',
                'Meldung'  => __('Spende gelöscht'),
            ]);
        }

        return redirect()->back()->with([
            'type'  => 'danger',
            'Meldung'  => __('Berechtigung fehlt'),
        ]);
    }
}
