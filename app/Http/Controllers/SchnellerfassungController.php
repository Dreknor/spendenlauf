<?php

namespace App\Http\Controllers;

use App\Actions\Startnummernvergabe;
use App\Enums\Kategorie;
use App\Enums\ZahlungsArt;
use App\Http\Requests\SponsoringRequest;
use App\Http\Requests\SponsorRequest;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Models\Zahlung;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Anmeldetisch am Lauftag: Läufer + Startnummer + Sponsoren + Spenden in einem Schritt.
 */
class SchnellerfassungController extends Controller
{
    public function create(Request $request)
    {
        $event = $this->event();

        return view('lauftag.schnellerfassung', [
            'event' => $event,
            'klassen' => Schulklasse::orderBy('stufe')->orderBy('name')->pluck('name', 'id'),
            'teams' => Teams::orderBy('name')->pluck('name', 'id'),
            'naechsteNummer' => (new Startnummernvergabe($event))->naechsteFreie(),
            'vorauswahl' => $request->filled('laeufer') ? Laeufer::find($request->integer('laeufer')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $event = $this->event();
        $user = $request->user();
        $istNeu = ! $request->filled('laeufer_id');

        $regeln = [
            'laeufer_id' => ['nullable', Rule::exists('laeufers', 'id')->where('event_id', $event->id)],
            'vorname' => [Rule::requiredIf($istNeu), 'nullable', 'string', 'max:100'],
            'nachname' => [Rule::requiredIf($istNeu), 'nullable', 'string', 'max:100'],
            'geburtsdatum' => ['nullable', 'date', 'before:today'],
            'geschlecht' => ['nullable', Rule::in(['weiblich', 'maennlich', 'divers'])],
            'email' => ['nullable', 'email'],
            'kategorie' => [Rule::requiredIf($istNeu), 'nullable', Rule::enum(Kategorie::class)],
            'schulklasse_id' => [
                'nullable',
                Rule::requiredIf($istNeu && $request->input('kategorie') === Kategorie::Schueler->value),
                Rule::exists('schulklassen', 'id')->where('event_id', $event->id),
            ],
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('event_id', $event->id)],
            'teamname' => ['nullable', 'string', 'max:100', Rule::unique('teams', 'name')->where('event_id', $event->id)],
            'startnummer' => ['nullable', 'integer', 'min:1'],
            'sponsoren' => ['nullable', 'array'],
            'sponsoren.*.sponsor_id' => ['nullable', 'integer', 'exists:sponsors,id'],
            'sponsoren.*.ziel' => ['nullable', 'in:laeufer,team'],
            'sponsoren.*.bar' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];

        foreach ((array) $request->input('sponsoren', []) as $i => $eintrag) {
            if (empty($eintrag['sponsor_id'])) {
                $regeln += SponsorRequest::regeln(null, $eintrag['anrede'] ?? null, "sponsoren.$i.");
            }
            $regeln += collect(SponsoringRequest::betragsRegeln("sponsoren.$i."))->except(["sponsoren.$i.projects", "sponsoren.$i.projects.*"])->all();
        }

        $validator = Validator::make($request->all(), $regeln, [], [
            'schulklasse_id' => 'Klasse',
            'sponsoren.*.nachname' => 'Nachname des Sponsors',
            'sponsoren.*.vorname' => 'Vorname des Sponsors',
            'sponsoren.*.firmenname' => 'Firmenname',
            'sponsoren.*.email' => 'E-Mail des Sponsors',
            'sponsoren.*.strasse' => 'Straße des Sponsors',
            'sponsoren.*.plz' => 'PLZ des Sponsors',
            'sponsoren.*.ort' => 'Ort des Sponsors',
        ]);
        $validator->after(function ($v) use ($request) {
            foreach ((array) $request->input('sponsoren', []) as $i => $eintrag) {
                SponsoringRequest::pruefeBetrag($v, $eintrag, "sponsoren.$i.");
                if (($eintrag['ziel'] ?? 'laeufer') === 'team' && ! $request->filled('team_id') && ! $request->filled('teamname')) {
                    $v->errors()->add("sponsoren.$i.ziel", 'Für eine Team-Spende muss der Läufer einem Team zugeordnet sein.');
                }
            }
        });
        $daten = $validator->validate();

        $ergebnis = DB::transaction(function () use ($daten, $event, $user, $istNeu) {
            $vergabe = new Startnummernvergabe($event);

            // Läufer
            if ($istNeu) {
                $laeufer = new Laeufer(collect($daten)->only(['vorname', 'nachname', 'geburtsdatum', 'geschlecht', 'email', 'kategorie'])->all());
                $laeufer->schulklasse_id = ($daten['kategorie'] ?? null) === Kategorie::Schueler->value ? ($daten['schulklasse_id'] ?? null) : null;
                $laeufer->verwaltet_von = $user->id;
            } else {
                $laeufer = Laeufer::findOrFail($daten['laeufer_id']);
            }

            if (! empty($daten['teamname'])) {
                $team = new Teams(['name' => $daten['teamname'], 'open' => true]);
                $team->verwaltet_von = $user->id;
                $team->save();
                $laeufer->team_id = $team->id;
            } elseif (! empty($daten['team_id'])) {
                $laeufer->team_id = $daten['team_id'];
            }
            $laeufer->save();

            $wunsch = isset($daten['startnummer']) ? (int) $daten['startnummer'] : null;
            if ($laeufer->startnummer === null || ($wunsch !== null && $wunsch !== $laeufer->startnummer)) {
                $vergabe->vergeben($laeufer, $wunsch);
            }

            // Sponsoren + Spenden
            $projekte = $event->projects()->pluck('projects.id');
            $anzahl = 0;
            $bar = 0;
            foreach ($daten['sponsoren'] ?? [] as $eintrag) {
                $sponsor = ! empty($eintrag['sponsor_id'])
                    ? Sponsor::findOrFail($eintrag['sponsor_id'])
                    : Sponsor::create(collect($eintrag)->only(['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'telefon', 'strasse', 'plz', 'ort'])->all());
                $sponsor->users()->syncWithoutDetaching([$user->id]);

                $sponsoring = new Sponsoring([
                    'sponsor_id' => $sponsor->id,
                    'festBetrag' => $eintrag['festBetrag'] ?? 0,
                    'rundenBetrag' => $eintrag['rundenBetrag'] ?? 0,
                    'maxBetrag' => $eintrag['maxBetrag'] ?? null,
                ]);
                $sponsoring->verwaltet_von = $user->id;
                $sponsoring->sponsorable()->associate(($eintrag['ziel'] ?? 'laeufer') === 'team' && $laeufer->team_id ? Teams::find($laeufer->team_id) : $laeufer);
                $sponsoring->save();
                $sponsoring->projects()->sync($projekte);
                $anzahl++;

                if (! empty($eintrag['bar']) && (float) $eintrag['bar'] > 0) {
                    $zahlung = new Zahlung([
                        'sponsor_id' => $sponsor->id,
                        'betrag' => $eintrag['bar'],
                        'bezahlt_am' => now()->toDateString(),
                        'art' => ZahlungsArt::Bar,
                        'notiz' => 'Bar am Anmeldetisch',
                    ]);
                    $zahlung->erfasst_von = $user->id;
                    $zahlung->save();
                    $bar += SpendenRechner::cent($eintrag['bar']);
                }
            }

            return [$laeufer, $anzahl, $bar];
        });

        [$laeufer, $anzahl, $bar] = $ergebnis;
        SpendenRechner::vergessen();

        $meldung = "{$laeufer->name}: Startnummer {$laeufer->startnummer}";
        $meldung .= $anzahl ? ", $anzahl Spende(n) erfasst" : '';
        $meldung .= $bar ? ', '.euro($bar).' bar kassiert' : '';

        return redirect()->route('schnellerfassung.create')->with(['type' => 'success', 'status' => $meldung.'.', 'letzte_startnummer' => $laeufer->startnummer]);
    }
}
