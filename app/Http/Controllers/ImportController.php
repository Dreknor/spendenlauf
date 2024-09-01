<?php

namespace App\Http\Controllers;

use App\Imports\RundenUpdate;
use App\Imports\RundenUpdateImport;
use App\Model\Laeufer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                'type'   => 'danger',
                'Meldung'    => __('Datei fehlt'),
            ]);
        }

        Excel::import(new RundenUpdateImport(), $request->file('file'));

        return redirect('home')->with([
            'type'  => 'success',
            'Meldung'   => __(Laeufer::where('updated_at', '>=', Carbon::now()->subSeconds(30))->count().' Imports abgeschlossen'),
        ]);
    }

    public function importFromUrl()
    {

        $runden_alt = Laeufer::query()->sum('runden');



        $url = config('config.import.url');

        if (empty($url)) {

            return redirect()->back()->with([
                'type'   => 'danger',
                'Meldung'    => __('URL fehlt'),
            ]);
        }

        $data = file_get_contents($url);

        $file = 'temp.csv';
        file_put_contents($file, $data);
        Excel::import(new RundenUpdate(), $file);
        unlink($file);

        $runden_neu = Laeufer::query()->sum('runden');

        Cache::clear();
        $string = 'Runden wurden aktualisiert. Anzahl der Runden vorher: '.$runden_alt.' Anzahl der Runden nachher: '.$runden_neu;

        return redirect('home')->with([
            'type'  => 'success',
            'Meldung'   => __( $string),
        ]);


    }
}
