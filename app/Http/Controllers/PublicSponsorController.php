<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicSponsorRequest;
use App\Mail\SpendenzusageBestaetigen;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Spendenzusage;
use App\Services\Dubletten;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Öffentliche Seite eines Läufers, über die Sponsoren selbst eine Spende zusagen können.
 * Die Zusage zählt erst, wenn der Sponsor sie über den Link in der Bestätigungsmail bestätigt (Double-Opt-in).
 */
class PublicSponsorController extends Controller
{
    private const SPONSOR_FELDER = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon'];

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

        $daten = collect($request->validated())
            ->only([...self::SPONSOR_FELDER, 'festBetrag', 'rundenBetrag', 'maxBetrag', 'projects'])
            ->all();

        $zusage = new Spendenzusage(['daten' => $daten]);
        $zusage->event_id = $event->id;
        $zusage->laeufer_id = $laeufer->id;
        $zusage->save();

        Mail::to($daten['email'])->queue(new SpendenzusageBestaetigen($zusage));

        return back()->with([
            'type' => 'success',
            'status' => "Fast geschafft! Wir haben Ihnen eine E-Mail an {$daten['email']} geschickt. Bitte bestätigen Sie Ihre Zusage über den Link darin – erst dann wird sie gezählt.",
        ]);
    }

    /** Seite hinter dem Link aus der Bestätigungsmail (bestätigt wird erst per Klick, nicht schon beim Öffnen). */
    public function bestaetigung(Request $request, int $zusage)
    {
        return $this->bestaetigungsSeite($request->hasValidSignature() ? Spendenzusage::find($zusage) : null);
    }

    public function bestaetigen(Request $request, int $zusage)
    {
        $vorhanden = $request->hasValidSignature() ? Spendenzusage::find($zusage) : null;

        if (! $vorhanden || $this->nichtMehrMoeglich($vorhanden)) {
            return $this->bestaetigungsSeite($vorhanden);
        }

        $laeufer = DB::transaction(function () use ($zusage) {
            // Sperre gegen Doppelklick: nur der erste Aufruf findet die Zusage noch vor
            $zusage = Spendenzusage::whereKey($zusage)->lockForUpdate()->first();
            if (! $zusage) {
                return null;
            }

            $laeufer = $zusage->laeufer;
            $event = $zusage->event;
            $daten = $zusage->daten;
            $sponsor = Dubletten::vorhandenerSponsor($daten) ?? Sponsor::create(collect($daten)->only(self::SPONSOR_FELDER)->all());

            if ($laeufer->verwaltet_von) {
                $sponsor->users()->syncWithoutDetaching([$laeufer->verwaltet_von]);
            }

            $sponsoring = new Sponsoring([
                'sponsor_id' => $sponsor->id,
                'festBetrag' => $daten['festBetrag'] ?? 0,
                'rundenBetrag' => $daten['rundenBetrag'] ?? 0,
                'maxBetrag' => $daten['maxBetrag'] ?? null,
            ]);
            $sponsoring->event_id = $event->id;
            $sponsoring->verwaltet_von = $laeufer->verwaltet_von;
            $sponsoring->sponsorable()->associate($laeufer);
            $sponsoring->save();

            $sponsoring->projects()->sync($daten['projects'] ?? $event->projects()->pluck('projects.id'));
            $zusage->delete();

            return $laeufer;
        });

        if (! $laeufer) {
            return $this->bestaetigungsSeite(null);
        }

        SpendenRechner::vergessen();

        return redirect()->route('public.sponsor.show', $laeufer->uuid)->with([
            'type' => 'success',
            'status' => "Vielen Dank! Ihre Spende für {$laeufer->vorname} ist bestätigt. Nach dem Lauf erhalten Sie eine E-Mail mit dem Spendenbetrag und den Zahlungsinformationen.",
        ]);
    }

    private function nichtMehrMoeglich(Spendenzusage $zusage): bool
    {
        return $zusage->laeufer === null || $zusage->event->laufVorbei();
    }

    private function bestaetigungsSeite(?Spendenzusage $zusage)
    {
        return view('public.zusage', [
            'zusage' => $zusage,
            'vorbei' => $zusage && $this->nichtMehrMoeglich($zusage),
        ]);
    }
}
