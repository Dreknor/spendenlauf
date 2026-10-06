<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Schulklasse;
use App\Models\Sponsoring;
use App\Services\SpendenRechner;
use App\Support\CurrentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::query()->orderByDesc('datum')->get()->map(function (Event $e) {
            $e->anzahl_laeufer = Laeufer::forEvent($e)->count();
            $e->anzahl_sponsorings = Sponsoring::forEvent($e)->count();
            $e->anzahl_klassen = Schulklasse::forEvent($e)->count();

            return $e;
        });

        return view('events.index', [
            'events' => $events,
            'aktuell' => app(CurrentEvent::class)->get(),
        ]);
    }

    public function create(Request $request)
    {
        $vorlage = Event::find($request->integer('vorlage')) ?? Event::orderByDesc('datum')->first();

        $event = $vorlage
            ? $vorlage->replicate(['aktiv', 'datum', 'anmeldeschluss', 'nummer', 'name', 'import_url'])
            : new Event(['runden_laenge_m' => 400, 'dauer_minuten' => 120, 'klassenkasse_prozent' => 10, 'verwendungszweck_prefix' => 'Spendenlauf', 'zahlungsziel_tage' => 14]);

        if ($vorlage) {
            $event->nummer = $vorlage->nummer ? $vorlage->nummer + 1 : null;
            $event->name = $event->nummer ? $event->nummer.'. Radebeuler Spendenlauf' : '';
            $event->datum = $vorlage->datum->copy()->addYears(2);
        }

        return view('events.form', [
            'event' => $event,
            'vorlage' => $vorlage,
            'projekte' => Projects::orderBy('name')->get(),
            'gewaehlteProjekte' => $vorlage ? $vorlage->projects()->pluck('projects.id')->all() : Projects::pluck('id')->all(),
        ]);
    }

    public function store(EventRequest $request)
    {
        $event = DB::transaction(function () use ($request) {
            $event = Event::create($request->eventDaten() + ['aktiv' => Event::count() === 0]);
            $event->projects()->sync($request->input('projects', []));

            $vorlage = Event::find($request->integer('vorlage_event_id'));
            if ($vorlage && $request->boolean('klassen_uebernehmen')) {
                $this->klassenUebernehmen($vorlage, $event);
            }

            return $event;
        });

        return $this->erfolg("„{$event->name}“ wurde angelegt.", route('events.index'));
    }

    public function edit(Event $event)
    {
        return view('events.form', [
            'event' => $event,
            'vorlage' => null,
            'projekte' => Projects::orderBy('name')->get(),
            'gewaehlteProjekte' => $event->projects()->pluck('projects.id')->all(),
        ]);
    }

    public function update(EventRequest $request, Event $event)
    {
        $event->update($request->eventDaten());
        $event->projects()->sync($request->input('projects', []));
        SpendenRechner::vergessen();

        return $this->erfolg('Einstellungen gespeichert.', route('events.index'));
    }

    public function destroy(Event $event)
    {
        if ($event->aktiv || Laeufer::forEvent($event)->withTrashed()->exists() || Sponsoring::forEvent($event)->exists()) {
            return $this->fehler('Nur inaktive Veranstaltungen ohne Läufer und Spenden können gelöscht werden.');
        }

        $event->delete();

        return $this->erfolg('Veranstaltung gelöscht.', route('events.index'));
    }

    /** Macht die Veranstaltung für alle Benutzer zur aktuellen. */
    public function aktivieren(Request $request, Event $event)
    {
        DB::transaction(function () use ($event) {
            Event::query()->update(['aktiv' => false]);
            $event->update(['aktiv' => true]);
        });

        $request->session()->forget(CurrentEvent::SESSION_KEY);

        return $this->erfolg("„{$event->name}“ ist jetzt die aktive Veranstaltung.");
    }

    /** Wechselt nur für den eigenen Benutzer die angezeigte Veranstaltung (z. B. um ältere Läufe anzusehen). */
    public function wechseln(Request $request, Event $event)
    {
        $request->session()->put(CurrentEvent::SESSION_KEY, $event->id);

        return $this->erfolg("Sie sehen jetzt die Daten von „{$event->name}“.", route('home'));
    }

    private function klassenUebernehmen(Event $vorlage, Event $neu): void
    {
        $jahre = $neu->jahr() - $vorlage->jahr();

        foreach (Schulklasse::forEvent($vorlage)->get() as $klasse) {
            $name = $klasse->name;
            $stufe = $klasse->stufe;
            if ($jahre > 0 && preg_match('/^(\d+)\s*(.*)$/u', $name, $m)) {
                $stufe = (int) $m[1] + $jahre;
                $name = $stufe.$m[2];
            }

            Schulklasse::forEvent($neu)->firstOrCreate(
                ['event_id' => $neu->id, 'name' => $name],
                ['stufe' => $stufe],
            );
        }
    }
}
