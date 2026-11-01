<?php

namespace App\Http\Controllers;

use App\Actions\LaeuferUebernehmen;
use App\Actions\Startnummernvergabe;
use App\Enums\Kategorie;
use App\Http\Requests\LaeuferRequest;
use App\Mail\AddLaeuferToTeam;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
use App\Models\Schulklasse;
use App\Models\Teams;
use App\Services\Dubletten;
use App\Services\SpendenRechner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class LaeuferController extends Controller
{
    private const SORTIERUNG = [
        'nachname' => ['nachname', 'vorname'],
        'startnummer' => ['startnummer'],
        'runden' => ['runden'],
        'geburtsdatum' => ['geburtsdatum'],
        'created_at' => ['created_at'],
    ];

    public function index(Request $request)
    {
        $event = $this->event();
        $user = $request->user();

        $sort = array_key_exists($request->input('sort'), self::SORTIERUNG) ? $request->input('sort') : 'nachname';
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $query = Laeufer::query()
            ->with(['team', 'schulklasse', 'besitzer'])
            ->unless($user->can('edit laeufer'), fn ($q) => $q->verwaltetVon($user))
            ->when($request->filled('q'), function ($q) use ($request) {
                $suche = '%'.$request->input('q').'%';
                $q->where(fn ($q) => $q->where('vorname', 'like', $suche)
                    ->orWhere('nachname', 'like', $suche)
                    ->orWhere('startnummer', $request->input('q')));
            })
            ->when($request->filled('kategorie'), fn ($q) => $q->where('kategorie', $request->input('kategorie')))
            ->when($request->filled('klasse'), fn ($q) => $q->where('schulklasse_id', $request->input('klasse')))
            ->when($request->filled('team'), fn ($q) => $q->where('team_id', $request->input('team')));

        foreach (self::SORTIERUNG[$sort] as $spalte) {
            $query->orderBy($spalte, $dir);
        }

        return view('laeufer.index', [
            'laeufer' => $query->paginate(50)->withQueryString(),
            'bilanz' => SpendenRechner::fuer($event)->bilanz(),
            'klassen' => Schulklasse::orderBy('stufe')->orderBy('name')->pluck('name', 'id'),
            'event' => $event,
        ]);
    }

    public function create(Request $request)
    {
        $event = $this->event();

        if (! $event->anmeldungOffen() && ! $request->user()->can('edit laeufer')) {
            return $this->fehler('Die Anmeldung für diesen Spendenlauf ist geschlossen.', route('laeufer.index'));
        }

        return view('laeufer.create', [
            'teams' => $this->waehlbareTeams($request),
            'klassen' => Schulklasse::orderBy('stufe')->orderBy('name')->pluck('name', 'id'),
            'event' => $event,
        ]);
    }

    public function store(LaeuferRequest $request)
    {
        $event = $this->event();
        $user = $request->user();

        if (! $event->anmeldungOffen() && ! $user->can('edit laeufer')) {
            return $this->fehler('Die Anmeldung für diesen Spendenlauf ist geschlossen.', route('laeufer.index'));
        }

        $daten = $request->laeuferDaten();

        if ($antwort = $this->dublettenPruefen($request, $event, $daten)) {
            return $antwort;
        }

        $laeufer = DB::transaction(function () use ($request, $daten, $event, $user) {
            $laeufer = new Laeufer($daten);
            $laeufer->verwaltet_von = $user->id;
            $laeufer->team_id = $this->teamFuerAnmeldung($request);
            $laeufer->save();

            $wunsch = $user->can('edit startnummer') ? ($request->integer('startnummer') ?: null) : null;
            (new Startnummernvergabe($event))->vergeben($laeufer, $wunsch);

            return $laeufer;
        });

        return $this->erfolg("{$laeufer->name} wurde angemeldet. Startnummer: {$laeufer->startnummer}", route('laeufer.edit', $laeufer));
    }

    public function show(Laeufer $laeufer)
    {
        return redirect()->route('laeufer.edit', $laeufer);
    }

    public function edit(Request $request, Laeufer $laeufer)
    {
        $this->authorize('view', $laeufer);
        $event = $this->event();

        $laeufer->load(['team.laeufer', 'schulklasse', 'sponsorings.sponsor.users', 'sponsorings.projects', 'besitzer', 'mitverwalter',
            'mitverwaltungAnfragen' => fn ($q) => $q->where('status', MitverwaltungAnfrage::OFFEN)->with('user')]);
        $rechner = SpendenRechner::fuer($event);

        return view('laeufer.edit', [
            'laeufer' => $laeufer,
            'event' => $event,
            'bilanz' => $rechner->bilanz(),
            'teams' => $this->waehlbareTeams($request),
            'klassen' => Schulklasse::orderBy('stufe')->orderBy('name')->pluck('name', 'id'),
            'teamSponsorings' => $laeufer->team ? $laeufer->team->sponsorings()->with('sponsor.users')->get() : collect(),
        ]);
    }

    public function update(LaeuferRequest $request, Laeufer $laeufer)
    {
        $this->authorize('update', $laeufer);
        $event = $this->event();
        $user = $request->user();

        if ($event->laufVorbei() && ! $user->can('edit laeufer')) {
            return $this->fehler('Nach dem Lauf können die Daten nicht mehr geändert werden.');
        }

        DB::transaction(function () use ($request, $laeufer, $user, $event) {
            $laeufer->fill($request->laeuferDaten());

            if ($user->can('edit runden') && $request->filled('runden') && (int) $request->input('runden') !== $laeufer->runden) {
                $laeufer->runden = $request->integer('runden');
                $laeufer->runden_manuell = true;
                $laeufer->runden_aktualisiert_am = now();
            }

            $laeufer->save();

            if ($user->can('edit startnummer') && $request->filled('startnummer') && $request->integer('startnummer') !== $laeufer->startnummer) {
                (new Startnummernvergabe($event))->vergeben($laeufer, $request->integer('startnummer'));
            }
        });

        return $this->erfolg('Daten wurden gespeichert.', route('laeufer.edit', $laeufer));
    }

    public function destroy(Laeufer $laeufer)
    {
        $this->authorize('delete', $laeufer);

        if ($laeufer->sponsorings()->exists()) {
            return $this->fehler('Der Läufer hat noch Sponsorings und kann nicht gelöscht werden.');
        }

        $nummer = $laeufer->startnummer;
        $laeufer->startnummer = null;
        $laeufer->save();
        $laeufer->delete();

        if ($nummer) {
            (new Startnummernvergabe($this->event()))->freigeben($nummer);
        }

        return $this->erfolg("{$laeufer->name} wurde abgemeldet.", route('laeufer.index'));
    }

    public function teamBeitreten(Request $request, Laeufer $laeufer)
    {
        $this->authorize('update', $laeufer);

        if ($antwort = $this->teamGesperrt($request)) {
            return $antwort;
        }

        $request->validate([
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('event_id', $this->event()->id)],
            'teamname' => ['nullable', 'required_without:team_id', 'string', 'max:100', Rule::unique('teams', 'name')->where('event_id', $this->event()->id)],
        ]);

        if ($request->filled('team_id')) {
            $team = Teams::findOrFail($request->integer('team_id'));
            $this->authorize('join', $team);
        } else {
            $team = new Teams(['name' => $request->input('teamname'), 'open' => true]);
            $team->verwaltet_von = $request->user()->id;
            $team->save();
        }

        $laeufer->team_id = $team->id;
        $laeufer->save();

        if ($team->besitzer && ! $team->istVerwaltetVon($request->user())) {
            $mail = Mail::to($team->besitzer->email);
            if ($admin = config('spendenlauf.admin_mail')) {
                $mail->bcc($admin);
            }
            $mail->queue(new AddLaeuferToTeam($team->besitzer->name, $laeufer->name, $team->name));
        }

        return $this->erfolg("{$laeufer->name} ist jetzt im Team „{$team->name}“.");
    }

    public function teamVerlassen(Request $request, Laeufer $laeufer)
    {
        $this->authorize('update', $laeufer);

        if ($antwort = $this->teamGesperrt($request)) {
            return $antwort;
        }

        $laeufer->team_id = null;
        $laeufer->save();

        return $this->erfolg("{$laeufer->name} wurde aus dem Team entfernt.");
    }

    public function bescheinigung(Laeufer $laeufer)
    {
        $this->authorize('view', $laeufer);

        return Pdf::loadView('pdf.bescheinigung', [
            'laeufer' => $laeufer,
            'event' => $this->event(),
        ])->stream('Bescheinigung-'.$laeufer->startnummer.'.pdf');
    }

    /** Läufer aus einem früheren Lauf übernehmen (für Eltern). */
    public function uebernehmen(Request $request)
    {
        $event = $this->event();

        if (! $event->anmeldungOffen() && ! $request->user()->can('edit laeufer')) {
            return $this->fehler('Die Anmeldung für diesen Spendenlauf ist geschlossen.', route('laeufer.index'));
        }

        $request->validate(['laeufer' => ['required', 'array'], 'laeufer.*' => ['integer']]);

        $alte = Laeufer::withoutGlobalScope('event')
            ->whereIn('id', $request->input('laeufer'))
            ->where('event_id', '!=', $event->id)
            ->unless($request->user()->can('edit laeufer'), fn ($q) => $q->verwaltetVon($request->user()))
            ->get();

        $uebernahme = new LaeuferUebernehmen($event);
        $neu = $uebernahme->ausfuehren($alte, $request->user());

        $ohneKlasse = $neu->filter(fn ($l) => $l->kategorie === Kategorie::Schueler && ! $l->schulklasse_id)->count();
        $meldung = $neu->count().' Läufer übernommen.';
        if ($ohneKlasse) {
            $meldung .= " Bitte bei $ohneKlasse Schüler(n) die Klasse prüfen.";
        }
        if ($uebernahme->bereitsAngemeldet->isNotEmpty()) {
            $meldung .= ' Bereits von einem anderen Konto angemeldet: '.$uebernahme->bereitsAngemeldet->pluck('name')->join(', ')
                .' – die Mitverwaltung wurde angefragt.';
        }

        return redirect()->route('laeufer.index')->with([
            'type' => $ohneKlasse || $uebernahme->bereitsAngemeldet->isNotEmpty() ? 'warning' : 'success',
            'status' => $meldung,
        ]);
    }

    /**
     * Prüft auf bereits angemeldete Läufer mit gleichem/ähnlichem Namen.
     * Sichere Treffer (gleicher Name + Geburtsdatum) darf nur das Orga-Team übergehen,
     * bei ähnlichen Treffern kann jeder bestätigen, dass es eine andere Person ist.
     */
    private function dublettenPruefen(LaeuferRequest $request, Event $event, array $daten): ?RedirectResponse
    {
        $kandidaten = Dubletten::laeuferKandidaten($event, $daten['vorname'], $daten['nachname'], $daten['geburtsdatum'] ?? null);

        if ($kandidaten->isEmpty()) {
            return null;
        }

        $sicher = $kandidaten->contains('grad', Dubletten::SICHER);
        $darfUebergehen = ! $sicher || $request->user()->can('edit laeufer');

        if ($darfUebergehen && $request->boolean('trotzdem_anlegen')) {
            return null;
        }

        return back()->withInput()->with([
            'dubletten' => $kandidaten->map(fn ($k) => ['id' => $k['laeufer']->id, 'grad' => $k['grad']])->all(),
            'dubletten_uebergehbar' => $darfUebergehen,
        ]);
    }

    /** Nach dem Lauf verändert ein Teamwechsel die Beträge der Team-Spenden – dann nur noch durch das Orga-Team. */
    private function teamGesperrt(Request $request): ?RedirectResponse
    {
        if ($this->event()->laufVorbei() && ! $request->user()->can('edit teams')) {
            return $this->fehler('Nach dem Lauf kann das Team nicht mehr gewechselt werden.');
        }

        return null;
    }

    private function waehlbareTeams(Request $request)
    {
        return Teams::query()
            ->unless($request->user()->can('edit teams'), fn ($q) => $q->where(fn ($q) => $q->where('verwaltet_von', $request->user()->id)->orWhere('open', true)))
            ->orderBy('name')
            ->get();
    }

    private function teamFuerAnmeldung(LaeuferRequest $request): ?int
    {
        if ($request->filled('team_id')) {
            $team = Teams::findOrFail($request->integer('team_id'));
            $this->authorize('join', $team);

            return $team->id;
        }

        if ($request->filled('teamname')) {
            $team = new Teams(['name' => $request->input('teamname'), 'open' => true]);
            $team->verwaltet_von = $request->user()->id;
            $team->save();

            return $team->id;
        }

        return null;
    }
}
