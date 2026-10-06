<?php

namespace App\Http\Controllers;

use App\Http\Requests\SponsoringRequest;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;

class SponsoringController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sponsorings = Sponsoring::query()
            ->with(['sponsorable', 'sponsor', 'projects'])
            ->sichtbarFuer($user)
            ->when($request->filled('q'), function ($q) use ($request) {
                $suche = '%'.$request->input('q').'%';
                $q->where(fn ($q) => $q
                    ->whereHas('sponsor', fn ($s) => $s->where('nachname', 'like', $suche)->orWhere('vorname', 'like', $suche)->orWhere('firmenname', 'like', $suche))
                    ->orWhereHasMorph('sponsorable', [Laeufer::class], fn ($l) => $l->where('nachname', 'like', $suche)->orWhere('vorname', 'like', $suche))
                    ->orWhereHasMorph('sponsorable', [Teams::class], fn ($t) => $t->where('name', 'like', $suche)));
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('sponsorings.index', [
            'sponsorings' => $sponsorings,
            'bilanz' => SpendenRechner::fuer($this->event())->bilanz(),
        ]);
    }

    public function create(Request $request)
    {
        $event = $this->event();
        $user = $request->user();

        if (! $event->anmeldungOffen() && ! $user->can('edit sponsorings')) {
            return $this->fehler('Für diesen Spendenlauf können keine Spenden mehr angelegt werden.', route('sponsorings.index'));
        }

        return view('sponsorings.create', [
            'event' => $event,
            'laeufer' => Laeufer::query()
                ->unless($user->can('edit laeufer'), fn ($q) => $q->verwaltetVon($user))
                ->orderBy('nachname')->orderBy('vorname')->get(),
            'sponsoren' => Sponsor::query()
                ->unless($user->can('edit sponsoren'), fn ($q) => $q->whereHas('users', fn ($u) => $u->whereKey($user->id)))
                ->orderBy('nachname')->orderBy('firmenname')->get(),
            'teams' => Teams::query()
                ->unless($user->can('edit teams'), fn ($q) => $q->where(fn ($q) => $q->where('verwaltet_von', $user->id)->orWhere('open', true)))
                ->orderBy('name')->get(),
            'projekte' => $event->projects()->orderBy('name')->get(),
        ]);
    }

    public function store(SponsoringRequest $request)
    {
        $event = $this->event();
        $user = $request->user();
        $daten = $request->validated();

        $sponsor = Sponsor::findOrFail($daten['sponsor_id']);
        $this->authorize('view', $sponsor);

        $ziel = $daten['type'] === 'team'
            ? Teams::findOrFail($daten['team_id'])
            : Laeufer::findOrFail($daten['laeufer_id']);

        if ($ziel instanceof Laeufer && ! $user->can('edit laeufer')) {
            $this->authorize('view', $ziel);
        }
        if ($ziel instanceof Teams) {
            $this->authorize('join', $ziel);
        }

        $sponsoring = new Sponsoring([
            'sponsor_id' => $sponsor->id,
            'festBetrag' => $daten['festBetrag'] ?? 0,
            'rundenBetrag' => $daten['rundenBetrag'] ?? 0,
            'maxBetrag' => $daten['maxBetrag'] ?? null,
        ]);
        $sponsoring->verwaltet_von = $user->id;
        $sponsoring->sponsorable()->associate($ziel);
        $sponsoring->save();

        $sponsoring->projects()->sync($daten['projects'] ?? $event->projects()->pluck('projects.id'));
        SpendenRechner::vergessen();

        return $this->erfolg('Die Spende wurde angelegt.', $request->input('zurueck') ?: route('sponsorings.index'));
    }

    public function destroy(Sponsoring $sponsoring)
    {
        $this->authorize('delete', $sponsoring);

        $sponsoring->projects()->detach();
        $sponsoring->delete();

        return $this->erfolg('Spende gelöscht.');
    }
}
