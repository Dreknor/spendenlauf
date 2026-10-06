<?php

namespace App\Http\Controllers;

use App\Models\Laeufer;
use App\Models\Teams;
use App\Services\SpendenRechner;
use App\Support\CurrentEvent;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $event = app(CurrentEvent::class)->get();
        $user = $request->user();

        if (! $event) {
            return view('home', ['event' => null]);
        }

        $bilanz = SpendenRechner::fuer($event)->bilanz();

        $meineLaeufer = $user->laeufer()->with(['team', 'schulklasse'])->orderBy('nachname')->get();

        $vorjahresLaeufer = collect();
        if ($event->anmeldungOffen()) {
            $vorjahresLaeufer = Laeufer::withoutGlobalScope('event')
                ->where('verwaltet_von', $user->id)
                ->where('event_id', '!=', $event->id)
                ->latest('event_id')
                ->get()
                ->unique(fn ($l) => mb_strtolower($l->vorname.'|'.$l->nachname))
                ->reject(fn ($l) => $meineLaeufer->contains(fn ($m) => mb_strtolower($m->vorname.'|'.$m->nachname) === mb_strtolower($l->vorname.'|'.$l->nachname)));
        }

        return view('home', [
            'event' => $event,
            'bilanz' => $bilanz,
            'anzahlLaeufer' => count($bilanz->laeufer),
            'anzahlTeams' => Teams::count(),
            'anzahlSponsoren' => count($bilanz->sponsoren),
            'meineLaeufer' => $meineLaeufer,
            'vorjahresLaeufer' => $vorjahresLaeufer,
        ]);
    }
}
