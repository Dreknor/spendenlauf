<?php

namespace App\Http\Controllers;

use App\Http\Requests\SponsorRequest;
use App\Models\Sponsor;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sponsoren = Sponsor::query()
            ->withCount('sponsorings')
            ->unless($user->can('edit sponsoren'), fn ($q) => $q->whereHas('users', fn ($u) => $u->whereKey($user->id)))
            ->when($request->filled('q'), function ($q) use ($request) {
                $suche = '%'.$request->input('q').'%';
                $q->where(fn ($q) => $q->where('vorname', 'like', $suche)->orWhere('nachname', 'like', $suche)
                    ->orWhere('firmenname', 'like', $suche)->orWhere('email', 'like', $suche)->orWhere('ort', 'like', $suche));
            })
            ->when($request->input('filter') === 'aktuell', fn ($q) => $q->has('sponsorings'))
            ->orderBy('nachname')->orderBy('firmenname')
            ->paginate(50)
            ->withQueryString();

        return view('sponsoren.index', [
            'sponsoren' => $sponsoren,
            'bilanz' => SpendenRechner::fuer($this->event())->bilanz(),
        ]);
    }

    public function create()
    {
        return view('sponsoren.create');
    }

    public function store(SponsorRequest $request)
    {
        $daten = $request->validated();

        $sponsor = Sponsor::query()
            ->where('nachname', $daten['nachname'] ?? null)
            ->where('vorname', $daten['vorname'] ?? null)
            ->where('firmenname', $daten['firmenname'] ?? null)
            ->where('plz', $daten['plz'] ?? null)
            ->first() ?? Sponsor::create($daten);

        $sponsor->users()->syncWithoutDetaching([$request->user()->id]);

        $ziel = $request->input('weiter') === 'sponsoring'
            ? route('sponsorings.create', ['sponsor' => $sponsor->id, 'laeufer' => $request->input('laeufer')])
            : route('sponsoren.index');

        return $this->erfolg("Sponsor „{$sponsor->name}“ wurde angelegt.", $ziel);
    }

    public function show(Sponsor $sponsor)
    {
        return redirect()->route('sponsoren.edit', $sponsor);
    }

    public function edit(Sponsor $sponsor)
    {
        $this->authorize('view', $sponsor);

        $sponsor->load(['sponsorings.sponsorable', 'sponsorings.projects']);
        $rechner = SpendenRechner::fuer($this->event());

        return view('sponsoren.edit', [
            'sponsor' => $sponsor,
            'bilanz' => $rechner->bilanz(),
            'konto' => $rechner->sponsorKonto($sponsor),
        ]);
    }

    public function update(SponsorRequest $request, Sponsor $sponsor)
    {
        $this->authorize('update', $sponsor);

        $sponsor->update($request->validated());

        return $this->erfolg('Sponsor gespeichert.');
    }
}
