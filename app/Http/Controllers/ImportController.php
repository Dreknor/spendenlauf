<?php

namespace App\Http\Controllers;

use App\Imports\RundenUpdate;
use App\Imports\RundenUpdateImport;
use App\Model\Laeufer;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function importFromUrl($test = false)
    {

        Log::info('Import von URL');
        $string = 'Strat Import von URL';

        if (!config('config.spendenlauf.date')->isToday() && !$test) {
            Log::info('Kein Spendenlauf heute');
            return null;
        }


        $runden_alt = Laeufer::query()->sum('runden');
        Log::info('Runden alt: '.$runden_alt);


        $url = config('config.import.url');

        if (empty($url)) {
            Log::info('Keine URL angegeben');
            return null;
        }

        try {
            $data = file_get_contents($url);
            Log::info('Import von URL');
            Log::info($url);
            Log::info($data);

            $pattern = '/Liste\/[a-zA-Z0-9]+\.csv/';
            preg_match($pattern, $data, $matches);

            Log::info('CSV-Datei gefunden: '.json_encode($matches));


            if (empty($matches)) {
                Log::error('Keine CSV-Datei gefunden');
                return null;
            }


            $url = 'https://www.berlin-timing.de/'.$matches[0];

            Log::info($url);

            Log::info('Import von URL - Hole CSV-Datei');
            $data = file_get_contents($url);

            if ($data === false) {
                Log::error('Fehler beim Lesen der Datei');
                return null;
            }

            $file = 'temp.csv';
            $csvContent = str_replace(';', ',', $csvContent);

            file_put_contents($file, $csvContent);

            Log::info("Datei heruntergeladen");

            try {
                Excel::import(new RundenUpdate(), $file);
                unlink($file);
                $runden_neu = Laeufer::query()->sum('runden');
            } catch (\Exception $e) {
                dd($e);
            }


            Cache::forget('sponsorings');
            $string = 'Runden wurden aktualisiert. Anzahl der Runden vorher: '.$runden_alt.' Anzahl der Runden nachher: '.$runden_neu;

        } catch (\Exception $e) {
            Log::error('Fehler beim Lesen der URL');
            Log::error($e);

            return null;
        }


        if ($test) {
            return $string;
        } else{
            Log::info($string);
        }

        return null;

    }
}
