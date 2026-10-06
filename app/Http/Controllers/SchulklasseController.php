<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchulklasseController extends Controller
{
    public function index(Event $event)
    {
        $klassen = Schulklasse::forEvent($event)->orderBy('stufe')->orderBy('name')->get();
        $anzahl = Laeufer::forEvent($event)->whereNotNull('schulklasse_id')
            ->selectRaw('schulklasse_id, count(*) as anzahl')->groupBy('schulklasse_id')->pluck('anzahl', 'schulklasse_id');

        return view('events.klassen', ['event' => $event, 'klassen' => $klassen, 'anzahl' => $anzahl]);
    }

    /**
     * Legt mehrere Klassen an. Eingabe z. B. „1a, 1b, 2a“ oder eine Klasse je Zeile.
     */
    public function store(Request $request, Event $event)
    {
        $request->validate(['klassen' => ['required', 'string', 'max:2000']]);

        $namen = collect(preg_split('/[\r\n,;]+/', $request->input('klassen')))
            ->map(fn ($n) => trim($n))->filter()->unique();

        $neu = 0;
        foreach ($namen as $name) {
            $klasse = Schulklasse::forEvent($event)->firstOrCreate(
                ['event_id' => $event->id, 'name' => mb_substr($name, 0, 50)],
                ['stufe' => preg_match('/^(\d+)/', $name, $m) ? (int) $m[1] : null],
            );
            $neu += $klasse->wasRecentlyCreated ? 1 : 0;
        }

        return $this->erfolg("$neu Klasse(n) angelegt.");
    }

    public function update(Request $request, Schulklasse $schulklasse)
    {
        $daten = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('schulklassen')->where('event_id', $schulklasse->event_id)->ignore($schulklasse->id)],
            'stufe' => ['nullable', 'integer', 'min:0', 'max:20'],
            'klassenleitung' => ['nullable', 'string', 'max:190'],
        ]);

        $schulklasse->update($daten);

        return $this->erfolg('Klasse gespeichert.');
    }

    public function destroy(Schulklasse $schulklasse)
    {
        if (Laeufer::withoutGlobalScope('event')->where('schulklasse_id', $schulklasse->id)->exists()) {
            return $this->fehler('Der Klasse sind noch Läufer zugeordnet.');
        }

        $schulklasse->delete();

        return $this->erfolg('Klasse gelöscht.');
    }
}
