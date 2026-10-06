<?php

namespace App\Http\Controllers;

use App\Models\Laeufer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Manuelle Rundeneingabe am Lauftag (z. B. bei Ausfall der Zeitmessung oder für Korrekturen).
 */
class RundenController extends Controller
{
    public function index()
    {
        return view('lauftag.runden', [
            'event' => $this->event(),
            'zuletzt' => Laeufer::whereNotNull('runden_aktualisiert_am')->where('runden_manuell', true)
                ->latest('runden_aktualisiert_am')->limit(15)->get(),
            'summe' => (int) Laeufer::sum('runden'),
        ]);
    }

    public function update(Request $request)
    {
        $daten = $request->validate([
            'startnummer' => ['required', 'integer', 'min:1'],
            'runden' => ['required', 'integer', 'min:-1000', 'max:1000'],
            'modus' => ['nullable', 'in:setzen,addieren'],
        ]);

        $laeufer = Laeufer::where('startnummer', $daten['startnummer'])->first();

        if (! $laeufer) {
            $meldung = "Startnummer {$daten['startnummer']} ist keinem Läufer zugeordnet.";

            return $request->expectsJson()
                ? response()->json(['message' => $meldung], 404)
                : $this->fehler($meldung);
        }

        $vorher = $laeufer->runden;
        $neu = ($daten['modus'] ?? 'setzen') === 'addieren' ? $vorher + $daten['runden'] : $daten['runden'];
        $laeufer->runden = max(0, $neu);
        $laeufer->runden_manuell = true;
        $laeufer->runden_aktualisiert_am = now();
        $laeufer->save();

        Log::info('Runden manuell geändert', [
            'startnummer' => $laeufer->startnummer, 'vorher' => $vorher, 'nachher' => $laeufer->runden, 'user' => $request->user()->id,
        ]);

        $meldung = "{$laeufer->name} (#{$laeufer->startnummer}): {$vorher} → {$laeufer->runden} Runden";

        return $request->expectsJson()
            ? response()->json(['message' => $meldung, 'name' => $laeufer->name, 'startnummer' => $laeufer->startnummer, 'vorher' => $vorher, 'runden' => $laeufer->runden])
            : $this->erfolg($meldung);
    }
}
