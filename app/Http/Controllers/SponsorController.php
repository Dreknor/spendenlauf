<?php

namespace App\Http\Controllers;

use App\Http\Requests\SponsorRequest;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\User;
use App\Services\Dubletten;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;

/**
 * Sponsoren sind gemeinsame Kontaktdatensätze: Sponsern z. B. Großeltern mehrere Enkel aus
 * verschiedenen Familien, nutzen alle denselben Sponsor. Jede Familie sieht aber nur die
 * Spenden für ihre eigenen Läufer.
 */
class SponsorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $alles = $this->siehtAlles($user);

        $sponsoren = Sponsor::query()
            ->with('users')
            ->withCount(['sponsorings' => fn ($q) => $q->sichtbarFuer($user)])
            ->unless($user->can('edit sponsoren'), fn ($q) => $q->whereHas('users', fn ($u) => $u->whereKey($user->id)))
            ->when($request->filled('q'), function ($q) use ($request) {
                $suche = '%'.$request->input('q').'%';
                $q->where(fn ($q) => $q->where('vorname', 'like', $suche)->orWhere('nachname', 'like', $suche)
                    ->orWhere('firmenname', 'like', $suche)->orWhere('email', 'like', $suche)->orWhere('ort', 'like', $suche));
            })
            ->when($request->input('filter') === 'aktuell', fn ($q) => $q->whereHas('sponsorings', fn ($s) => $s->sichtbarFuer($user)))
            ->orderBy('nachname')->orderBy('firmenname')
            ->paginate(50)
            ->withQueryString();

        $bilanz = SpendenRechner::fuer($this->event())->bilanz();
        $summen = $alles
            ? collect($sponsoren->items())->mapWithKeys(fn ($s) => [$s->id => $bilanz->sponsorSoll($s->id)])
            : Sponsoring::sichtbarFuer($user)->whereIn('sponsor_id', collect($sponsoren->items())->pluck('id'))->get(['id', 'sponsor_id'])
                ->groupBy('sponsor_id')->map(fn ($s) => $s->sum(fn ($sp) => $bilanz->sponsorings[$sp->id] ?? 0));

        return view('sponsoren.index', ['sponsoren' => $sponsoren, 'summen' => $summen]);
    }

    public function create()
    {
        return view('sponsoren.create');
    }

    public function store(SponsorRequest $request)
    {
        $daten = $request->validated();
        $user = $request->user();

        // Gleicher Sponsor (E-Mail oder gleicher Name + PLZ) → vorhandenen Datensatz verwenden
        $sponsor = Dubletten::vorhandenerSponsor($daten);
        $meldung = $sponsor ? "„{$sponsor->name}“ war bereits als Sponsor erfasst und wurde Ihnen zugeordnet." : null;

        // Ähnliche Sponsoren: nachfragen, ob einer davon gemeint ist
        if (! $sponsor) {
            $aehnliche = Dubletten::sponsorKandidaten($daten);

            if ($request->filled('vorhandener_sponsor')) {
                $sponsor = $aehnliche->pluck('sponsor')->firstWhere('id', $request->integer('vorhandener_sponsor'));
                abort_if($sponsor === null, 422, 'Ungültige Auswahl.');
                $meldung = "Der vorhandene Sponsor „{$sponsor->name}“ wurde Ihnen zugeordnet.";
            } elseif ($aehnliche->isNotEmpty() && ! $request->boolean('trotzdem_anlegen')) {
                return back()->withInput()->with('sponsor_dubletten', $aehnliche->map(fn ($k) => [
                    'id' => $k['sponsor']->id,
                    'name' => $k['sponsor']->name,
                    'ort' => trim($k['sponsor']->plz.' '.$k['sponsor']->ort),
                ])->all());
            }
        }

        $sponsor ??= Sponsor::create($daten);
        $sponsor->users()->syncWithoutDetaching([$user->id]);

        $ziel = $request->input('weiter') === 'sponsoring'
            ? route('sponsorings.create', ['sponsor' => $sponsor->id, 'laeufer' => $request->input('laeufer')])
            : route('sponsoren.index');

        return $this->erfolg($meldung ?? "Sponsor „{$sponsor->name}“ wurde angelegt.", $ziel);
    }

    public function show(Sponsor $sponsor)
    {
        return redirect()->route('sponsoren.edit', $sponsor);
    }

    public function edit(Request $request, Sponsor $sponsor)
    {
        $this->authorize('view', $sponsor);
        $user = $request->user();
        $alles = $this->siehtAlles($user);

        $sponsorings = Sponsoring::where('sponsor_id', $sponsor->id)->sichtbarFuer($user)
            ->with(['sponsorable', 'projects', 'sponsor'])->get();
        $rechner = SpendenRechner::fuer($this->event());
        $bilanz = $rechner->bilanz();

        return view('sponsoren.edit', [
            'sponsor' => $sponsor,
            'sponsorings' => $sponsorings,
            'bilanz' => $bilanz,
            'summe' => $sponsorings->sum(fn ($s) => $bilanz->sponsorings[$s->id] ?? 0),
            // Zahlungsstand betrifft alle Spenden des Sponsors → nur für das Orga-Team
            'konto' => $alles ? $rechner->sponsorKonto($sponsor) : null,
        ]);
    }

    public function update(SponsorRequest $request, Sponsor $sponsor)
    {
        $this->authorize('update', $sponsor);

        $sponsor->update($request->validated());

        return $this->erfolg('Sponsor gespeichert.');
    }

    private function siehtAlles(User $user): bool
    {
        return $user->can('edit sponsorings') || $user->can('manage abrechnung');
    }
}
