<?php

namespace App\Http\Controllers;

use App\Imports\RundenUpdateImport;
use App\Model\Laeufer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:import export');
    }

    public function import()
    {
        return view('import.create');
    }

    public function importFile(Request $request)
    {
        if (! $request->hasFile('file')) {
            return redirect()->back()->with([
                'type'   => 'danher',
                'Meldung'    => __('Datei fehlt'),
            ]);
        }

        Excel::import(new RundenUpdateImport(), $request->file('file'));

        return redirect('home')->with([
            'type'  => 'success',
            'Meldung'   => __(Laeufer::where('updated_at', '>=', Carbon::now()->subSeconds(30))->count().' Imports abgeschlossen'),
        ]);
    }
}
