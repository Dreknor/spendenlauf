<?php

namespace App\Http\Controllers;

use App\Exports\LaeuferExport;
use App\Exports\ProjectExport;
use App\Exports\SponsorExport;
use App\Model\Laeufer;
use App\Model\Projects;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use App\Model\Teams;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function laeufer()
    {
        if (! auth()->user()->can('import export')) {
            return redirect()->back()->with([
                'type'   => 'danger',
                'Meldung'    => __('Berechtigung fehlt'),
            ]);
        }

        return Excel::download(new LaeuferExport, 'laeufer.xlsx');
    }

    public function sponsoren()
    {
        if (! auth()->user()->can('import export')) {
            return redirect()->back()->with([
                'type'   => 'danger',
                'Meldung'    => __('Berechtigung fehlt'),
            ]);
        }

        return Excel::download(new SponsorExport(), 'sponsoren.xlsx');
    }

    public function projects()
    {
        $projects = Projects::all();
        $projectArray = [];
        $Spende = 0;

        foreach ($projects as $project) {
            $projectArray[$project->id] = [
                'name'  => $project->name,
                'spendensumme'  => 0,
            ];
        }

        $laeufers = Laeufer::all();
        $laeufers->load('sponsorings');
        $teams = Teams::with(['laeufer', 'sponsorings', 'sponsorings.sponsorable', 'sponsorings.projects'])->get();

        $Test = [];

        foreach ($teams as $team) {
            $runden = $team->laeufer->sum('runden');

            foreach ($team->sponsorings as $sponsoring) {
                $Spende = $sponsoring->spende($runden);
                $spendenaufteilung = 0;
                foreach ($sponsoring->projects as $project) {
                    $spendenaufteilung += floor(($Spende * 100) / ($sponsoring->projects->count())) / 100;
                    $projectArray[$project->id]['spendensumme'] += floor(($Spende * 100) / ($sponsoring->projects->count())) / 100;
                }
                if ($spendenaufteilung != $Spende) {
                    $Rest = $Spende - $spendenaufteilung;
                    $projectArray[$sponsoring->projects->first()->id]['spendensumme'] += $Rest;
                }
            }
        }

        foreach ($laeufers as $laeufer) {
            $runden = $laeufer->runden;

            foreach ($laeufer->sponsorings as $sponsoring) {
                $Spende = $sponsoring->spende($runden);
                $spendenaufteilung = 0;

                foreach ($sponsoring->projects as $project) {
                    $spendenaufteilung += floor(($Spende * 100) / ($sponsoring->projects->count())) / 100;
                    $projectArray[$project->id]['spendensumme'] += floor(($Spende * 100) / ($sponsoring->projects->count())) / 100;
                }
                if ($spendenaufteilung != $Spende) {
                    $Rest = $Spende - $spendenaufteilung;
                    $projectArray[$sponsoring->projects->first()->id]['spendensumme'] += $Rest;
                }
            }
        }

        return Excel::download(new ProjectExport($projectArray), 'Projektspenden.xlsx');
    }
}
