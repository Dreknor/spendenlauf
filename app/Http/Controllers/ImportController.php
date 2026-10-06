<?php

namespace App\Http\Controllers;

use App\Actions\ImportRunden;
use App\Models\Laeufer;
use Illuminate\Http\Request;
use Throwable;

class ImportController extends Controller
{
    public function create()
    {
        return view('lauftag.import', [
            'event' => $this->event(),
            'ergebnis' => session('import_ergebnis'),
            'summe' => (int) Laeufer::sum('runden'),
            'manuell' => Laeufer::where('runden_manuell', true)->count(),
        ]);
    }

    public function datei(Request $request)
    {
        $request->validate([
            'datei' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'manuelle_ueberschreiben' => ['nullable', 'boolean'],
        ]);

        return $this->ausfuehren(fn (ImportRunden $import) => $import->ausDatei(
            $request->file('datei')->getRealPath(),
            $request->file('datei')->getClientOriginalName(),
        ), $request->boolean('manuelle_ueberschreiben'));
    }

    public function url(Request $request)
    {
        $request->validate([
            'url' => ['nullable', 'url'],
            'manuelle_ueberschreiben' => ['nullable', 'boolean'],
        ]);

        return $this->ausfuehren(fn (ImportRunden $import) => $import->vonUrl($request->input('url')), $request->boolean('manuelle_ueberschreiben'));
    }

    private function ausfuehren(callable $schritt, bool $manuelleUeberschreiben)
    {
        try {
            $ergebnis = $schritt(new ImportRunden($this->event(), $manuelleUeberschreiben));
        } catch (Throwable $e) {
            report($e);

            return $this->fehler('Import fehlgeschlagen: '.$e->getMessage());
        }

        return redirect()->route('import.create')->with([
            'type' => 'success',
            'status' => "{$ergebnis['aktualisiert']} Läufer aktualisiert.",
            'import_ergebnis' => $ergebnis,
        ]);
    }
}
