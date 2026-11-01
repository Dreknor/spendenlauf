<?php

namespace App\Http\Controllers;

use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
use App\Models\MitverwaltungEinladung;
use App\Models\Teams;
use App\Services\SpendenRechner;
use App\Support\CurrentEvent;
use App\Support\Namen;
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

        $meineLaeufer = Laeufer::verwaltetVon($user)->with(['team', 'schulklasse'])->orderBy('nachname')->get();

        $vorjahresLaeufer = collect();
        if ($event->anmeldungOffen()) {
            $vorjahresLaeufer = Laeufer::withoutGlobalScope('event')
                ->verwaltetVon($user)
                ->where('event_id', '!=', $event->id)
                ->latest('event_id')
                ->get()
                ->unique(fn ($l) => mb_strtolower($l->vorname.'|'.$l->nachname))
                ->reject(fn ($l) => $meineLaeufer->contains(fn ($m) => Namen::normalisiere($m->vorname.$m->nachname) === Namen::normalisiere($l->vorname.$l->nachname)));
        }

        return view('home', [
            'event' => $event,
            'bilanz' => $bilanz,
            'anzahlLaeufer' => count($bilanz->laeufer),
            'anzahlTeams' => Teams::count(),
            'anzahlSponsoren' => count($bilanz->sponsoren),
            'meineLaeufer' => $meineLaeufer,
            'vorjahresLaeufer' => $vorjahresLaeufer,
            // Anfragen anderer Konten zu meinen Läufern (bzw. alle für das Orga-Team)
            'anfragen' => MitverwaltungAnfrage::query()
                ->where('status', MitverwaltungAnfrage::OFFEN)
                ->whereHas('laeufer', fn ($q) => $q->where('event_id', $event->id)
                    ->unless($user->can('edit laeufer'), fn ($q) => $q->where('verwaltet_von', $user->id)))
                ->with(['laeufer', 'user'])
                ->get(),
            'eigeneAnfragen' => MitverwaltungAnfrage::where('user_id', $user->id)->where('status', MitverwaltungAnfrage::OFFEN)->with('laeufer')->get(),
            // Einladungen anderer Konten an meine E-Mail-Adresse
            'einladungen' => MitverwaltungEinladung::fuer($user)->offen()
                ->whereHas('laeufer', fn ($q) => $q->where('event_id', $event->id))
                ->with(['laeufer', 'einlader'])
                ->get(),
        ]);
    }
}
