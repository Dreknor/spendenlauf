<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicSponsorRequest;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Services\Dubletten;
use App\Services\SpendenRechner;
use Illuminate\Support\Facades\DB;

/**
 * Öffentliche Seite eines Läufers, über die Sponsoren selbst eine Spende zusagen können.
 */
class PublicSponsorController extends Controller
{
    public function show(string $uuid)
    {
        $laeufer = Laeufer::with(['team', 'sponsorings'])->where('uuid', $uuid)->firstOrFail();
        $event = $this->event();

        return view('public.laeufer', [
            'laeufer' => $laeufer,
            'event' => $event,
            'projekte' => $event->projects()->orderBy('name')->get(),
            'spende' => SpendenRechner::fuer($event)->bilanz()->laeuferSpende($laeufer->id),
        ]);
    }

    public function store(PublicSponsorRequest $request, string $uuid)
    {
        $laeufer = Laeufer::where('uuid', $uuid)->firstOrFail();
        $event = $this->event();

        if (! $event->anmeldungOffen()) {
            return $this->fehler('Für diesen Spendenlauf können keine Spenden mehr zugesagt werden.');
        }

        $daten = $request->validated();

        DB::transaction(function () use ($daten, $laeufer, $event) {
            $sponsor = Dubletten::vorhandenerSponsor($daten) ?? Sponsor::create(collect($daten)->only([
                'anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon',
            ])->all());

            if ($laeufer->verwaltet_von) {
                $sponsor->users()->syncWithoutDetaching([$laeufer->verwaltet_von]);
            }

            $sponsoring = new Sponsoring([
                'sponsor_id' => $sponsor->id,
                'festBetrag' => $daten['festBetrag'] ?? 0,
                'rundenBetrag' => $daten['rundenBetrag'] ?? 0,
                'maxBetrag' => $daten['maxBetrag'] ?? null,
            ]);
            $sponsoring->verwaltet_von = $laeufer->verwaltet_von;
            $sponsoring->sponsorable()->associate($laeufer);
            $sponsoring->save();

            $sponsoring->projects()->sync($daten['projects'] ?? $event->projects()->pluck('projects.id'));
        });

        SpendenRechner::vergessen();

        return back()->with([
            'type' => 'success',
            'status' => 'Vielen Dank für Ihre Unterstützung! Nach dem Lauf erhalten Sie eine E-Mail mit dem Spendenbetrag und den Zahlungsinformationen.',
        ]);
    }
}
