<?php

namespace App\Http\Controllers;

use App\Models\Laeufer;
use App\Models\Startnummer;
use Illuminate\Http\Request;

class StartnummerController extends Controller
{
    public function index()
    {
        $vergeben = Laeufer::whereNotNull('startnummer')->pluck('startnummer');

        return view('startnummern.index', [
            'frei' => Startnummer::whereNotIn('startnummer', $vergeben)->orderBy('startnummer')->pluck('startnummer'),
            'vergebenAnzahl' => $vergeben->count(),
            'hoechste' => $vergeben->max(),
        ]);
    }

    public function store(Request $request)
    {
        $daten = $request->validate([
            'von' => ['required', 'integer', 'min:1'],
            'bis' => ['required', 'integer', 'gte:von', 'lte:'.((int) $request->input('von') + 5000)],
        ]);

        $event = $this->event();
        $vorhanden = Startnummer::pluck('startnummer')->merge(Laeufer::withTrashed()->whereNotNull('startnummer')->pluck('startnummer'))->flip();

        $neu = collect(range($daten['von'], $daten['bis']))
            ->reject(fn ($n) => isset($vorhanden[$n]))
            ->map(fn ($n) => ['event_id' => $event->id, 'startnummer' => $n, 'created_at' => now(), 'updated_at' => now()]);

        foreach ($neu->chunk(500) as $chunk) {
            Startnummer::insert($chunk->values()->all());
        }

        return $this->erfolg($neu->count().' Startnummern wurden angelegt.');
    }

    public function destroy(Startnummer $startnummer)
    {
        $startnummer->delete();

        return $this->erfolg("Startnummer {$startnummer->startnummer} wurde entfernt.");
    }

    public function destroyMany()
    {
        $anzahl = Startnummer::query()->delete();

        return $this->erfolg("$anzahl freie Startnummern wurden entfernt.");
    }
}
