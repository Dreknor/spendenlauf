<?php

namespace App\Http\Controllers;

use App\Enums\Kategorie;
use App\Exports\TabellenExport;
use App\Models\Schulklasse;
use App\Services\Statistik;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class AuswertungController extends Controller
{
    private function statistik(): Statistik
    {
        return Statistik::fuer($this->event());
    }

    public function uebersicht()
    {
        $s = $this->statistik();
        $altersgruppen = $s->nachDimension('altersgruppe');
        $kategorien = $s->nachDimension('kategorie');
        $zeilen = $s->laeuferZeilen();

        return view('auswertung.uebersicht', [
            'event' => $s->event(),
            'u' => $s->uebersicht(),
            'altersgruppen' => $altersgruppen,
            'kategorien' => $kategorien,
            'histogramm' => $s->rundenHistogramm(),
            'projekte' => $s->projekte(),
            'topRunden' => $zeilen->sortByDesc('runden')->take(10)->values(),
            'topSpende' => $zeilen->sortByDesc('spende')->take(10)->values(),
            'topTeams' => $s->teams()->sortByDesc('runden')->take(5)->values(),
        ]);
    }

    public function laeufer(Request $request)
    {
        $s = $this->statistik();
        $zeilen = $this->gefilterteZeilen($s, $request);

        $sort = in_array($request->input('sort'), ['runden', 'spende', 'nachname', 'alter', 'startnummer', 'km']) ? $request->input('sort') : 'runden';
        $zeilen = $request->input('dir', $sort === 'nachname' ? 'asc' : 'desc') === 'asc'
            ? $zeilen->sortBy($sort)->values()
            : $zeilen->sortByDesc($sort)->values();

        return view('auswertung.laeufer', [
            'event' => $s->event(),
            'zeilen' => $zeilen,
            'summen' => $s->kennzahlen($zeilen),
            'top' => $request->input('ansicht') === 'top' ? $s->topJeAltersgruppe((int) $request->input('anzahl', 3), $request->boolean('nach_geschlecht')) : null,
            'filter' => $this->filterOptionen($s),
        ]);
    }

    public function teams()
    {
        $s = $this->statistik();
        $teams = $s->teams()->sortByDesc('runden')->values();

        return view('auswertung.teams', ['event' => $s->event(), 'teams' => $teams]);
    }

    public function altersgruppen()
    {
        $s = $this->statistik();
        $gruppen = $s->nachDimension('altersgruppe');

        // Kreuztabelle Altersgruppe × Geschlecht
        $kreuz = $gruppen->map(fn ($g) => [
            'label' => $g['label'],
            'geschlechter' => $s->nachDimension('geschlecht', $g['zeilen'])->keyBy('label'),
        ]);

        return view('auswertung.altersgruppen', [
            'event' => $s->event(),
            'gruppen' => $gruppen,
            'kreuz' => $kreuz,
            'geschlechter' => $s->nachDimension('geschlecht')->pluck('label'),
        ]);
    }

    public function gruppen(Request $request)
    {
        $s = $this->statistik();
        $dim = array_key_exists($request->input('dim'), Statistik::DIMENSIONEN) ? $request->input('dim') : 'kategorie';

        return view('auswertung.gruppen', [
            'event' => $s->event(),
            'dim' => $dim,
            'gruppen' => $s->nachDimension($dim, $this->gefilterteZeilen($s, $request)),
            'filter' => $this->filterOptionen($s),
        ]);
    }

    public function klassen()
    {
        $s = $this->statistik();

        return view('auswertung.klassen', [
            'event' => $s->event(),
            'klassen' => $s->klassen(),
            'u' => $s->uebersicht(),
            'ohneKlasse' => $s->laeuferZeilen()->where('kategorie_key', Kategorie::Schueler->value)->whereNull('klasse_id')->values(),
        ]);
    }

    public function klasse(Schulklasse $schulklasse)
    {
        $s = $this->statistik();
        $klasse = $s->klassen()->firstWhere('id', $schulklasse->id);
        abort_if($klasse === null, 404);

        return view('auswertung.klasse', [
            'event' => $s->event(),
            'klasse' => $klasse,
            'zeilen' => $klasse['zeilen']->sortByDesc('runden')->values(),
        ]);
    }

    public function sponsoren()
    {
        $s = $this->statistik();

        return view('auswertung.sponsoren', [
            'event' => $s->event(),
            'sponsoren' => $s->sponsoren(),
            'verteilung' => $s->spendenVerteilung(),
            'u' => $s->uebersicht(),
        ]);
    }

    public function projekte()
    {
        $s = $this->statistik();

        return view('auswertung.projekte', [
            'event' => $s->event(),
            'projekte' => $s->projekte(),
            'u' => $s->uebersicht(),
        ]);
    }

    public function jahresvergleich()
    {
        return view('auswertung.jahresvergleich', ['events' => Statistik::jahresvergleich()]);
    }

    public function export(Request $request, string $bereich)
    {
        [$titel, $kopf, $zeilen] = $this->tabelle($bereich, $request);

        return Excel::download(new TabellenExport($titel, $kopf, $zeilen), 'Auswertung-'.$bereich.'-'.now()->format('Y-m-d').'.xlsx');
    }

    public function pdf(string $bereich)
    {
        $s = $this->statistik();

        $pdf = match ($bereich) {
            'uebersicht' => Pdf::loadView('pdf.auswertung-uebersicht', [
                'event' => $s->event(),
                'u' => $s->uebersicht(),
                'altersgruppen' => $s->nachDimension('altersgruppe'),
                'kategorien' => $s->nachDimension('kategorie'),
                'projekte' => $s->projekte(),
                'teams' => $s->teams()->sortByDesc('runden')->take(10),
                'top' => $s->topJeAltersgruppe(3),
            ]),
            'klassen' => Pdf::loadView('pdf.klassen', [
                'event' => $s->event(),
                'klassen' => $s->klassen(),
            ]),
            default => abort(404),
        };

        return $pdf->stream("Auswertung-$bereich.pdf");
    }

    // ------------------------------------------------------------------

    private function gefilterteZeilen(Statistik $s, Request $request): Collection
    {
        return $s->laeuferZeilen()
            ->when($request->filled('altersgruppe'), fn ($z) => $z->where('altersgruppe_key', $request->input('altersgruppe')))
            ->when($request->filled('geschlecht'), fn ($z) => $z->where('geschlecht_key', $request->input('geschlecht')))
            ->when($request->filled('kategorie'), fn ($z) => $z->where('kategorie_key', $request->input('kategorie')))
            ->when($request->filled('klasse'), fn ($z) => $z->where('klasse_id', (int) $request->input('klasse')))
            ->when($request->filled('stufe'), fn ($z) => $z->where('stufe', (int) $request->input('stufe')))
            ->when($request->input('nur') === 'teilnehmer', fn ($z) => $z->where('runden', '>', 0))
            ->values();
    }

    private function filterOptionen(Statistik $s): array
    {
        $event = $s->event();

        return [
            'altersgruppe' => collect($event->altersgruppenListe())->pluck('label', 'key')->put('unbekannt', 'unbekannt')->all(),
            'geschlecht' => Statistik::GESCHLECHTER + ['unbekannt' => 'keine Angabe'],
            'kategorie' => Kategorie::options() + ['unbekannt' => 'keine Angabe'],
            'klasse' => Schulklasse::forEvent($event)->orderBy('stufe')->orderBy('name')->pluck('name', 'id')->all(),
            'stufe' => Schulklasse::forEvent($event)->whereNotNull('stufe')->distinct()->orderBy('stufe')->pluck('stufe')->mapWithKeys(fn ($s) => [$s => "Klasse $s"])->all(),
        ];
    }

    /**
     * Tabellendaten für den Excel-Export.
     *
     * @return array{0: string, 1: list<string>, 2: list<list<mixed>>}
     */
    private function tabelle(string $bereich, Request $request): array
    {
        $s = $this->statistik();
        $e = fn (int $cent) => round($cent / 100, 2);
        $kennzahlenKopf = ['Läufer', 'Teilnehmer', 'Runden', 'Ø Runden', 'Max. Runden', 'km', 'Spenden (€)', 'Ø Spende je Läufer (€)', 'Spende je Runde (€)'];
        $kennzahlen = fn (array $g) => [$g['anzahl'], $g['teilnehmer'], $g['runden'], $g['runden_schnitt'], $g['runden_max'], $g['km'], $e($g['spende']), $e($g['spende_schnitt']), $e($g['spende_pro_runde'])];

        return match ($bereich) {
            'laeufer' => [
                'Läufer',
                ['Startnr.', 'Nachname', 'Vorname', 'Alter', 'Altersgruppe', 'Geschlecht', 'Kategorie', 'Klasse', 'Team', 'Runden', 'km', 'Spenden gesamt (€)', 'davon Einzel (€)', 'davon Teamanteil (€)', 'Sponsorings'],
                $this->gefilterteZeilen($s, $request)->sortByDesc('runden')->map(fn ($z) => [
                    $z['startnummer'], $z['nachname'], $z['vorname'], $z['alter'], $z['altersgruppe'], $z['geschlecht'], $z['kategorie'],
                    $z['klasse'], $z['team'], $z['runden'], $z['km'], $e($z['spende']), $e($z['einzel']), $e($z['teamanteil']), $z['sponsorings'],
                ])->values()->all(),
            ],
            'teams' => [
                'Teams',
                ['Team', 'Verantwortlich', 'Mitglieder', 'Teilnehmer', 'Runden', 'Ø Runden', 'km', 'Team-Spenden (€)', 'Einzelspenden Mitglieder (€)', 'Gesamt (€)'],
                $s->teams()->sortByDesc('runden')->map(fn ($t) => [
                    $t['name'], $t['besitzer'], $t['mitglieder'], $t['teilnehmer'], $t['runden'], $t['runden_schnitt'], $t['km'],
                    $e($t['team_spende']), $e($t['einzel_spende']), $e($t['gesamt']),
                ])->values()->all(),
            ],
            'klassen' => [
                'Klassen',
                ['Klasse', 'Klassenleitung', 'Schüler', 'Teilnehmer', 'Runden', 'Ø Runden', 'km', 'Spenden Schüler (€)', 'Klassenkasse (€)', 'davon bezahlt (€)', 'bezahlt (%)'],
                $s->klassen()->map(fn ($k) => [
                    $k['name'], $k['klassenleitung'], $k['schueler'], $k['teilnehmer'], $k['runden'], $k['runden_schnitt'], $k['km'],
                    $e($k['brutto']), $e($k['kasse']), $e($k['kasse_bezahlt']), $k['bezahlt_quote'],
                ])->values()->all(),
            ],
            'altersgruppen', 'gruppen' => (function () use ($s, $request, $bereich, $kennzahlen, $kennzahlenKopf) {
                $dim = $bereich === 'altersgruppen' ? 'altersgruppe' : (array_key_exists($request->input('dim'), Statistik::DIMENSIONEN) ? $request->input('dim') : 'kategorie');

                return [
                    Statistik::DIMENSIONEN[$dim],
                    array_merge([Statistik::DIMENSIONEN[$dim]], $kennzahlenKopf),
                    $s->nachDimension($dim, $this->gefilterteZeilen($s, $request))->map(fn ($g) => array_merge([$g['label']], $kennzahlen($g)))->all(),
                ];
            })(),
            'sponsoren' => [
                'Sponsoren',
                ['Sponsor', 'Ort', 'Sponsorings', 'Soll (€)', 'Bezahlt (€)'],
                $s->sponsoren()->map(fn ($sp) => [$sp['name'], $sp['ort'], $sp['sponsorings'], $e($sp['soll']), $e($sp['ist'])])->all(),
            ],
            'projekte' => [
                'Verteilung',
                ['Empfänger', 'Betrag (€)'],
                $s->projekte()->map(fn ($p) => [$p['label'], $e($p['betrag'])])->all(),
            ],
            'jahresvergleich' => [
                'Jahresvergleich',
                ['Veranstaltung', 'Datum', 'Läufer', 'Teilnehmer', 'Runden', 'km', 'Spenden (€)', 'Sponsoren', 'Bezahlt (€)'],
                Statistik::jahresvergleich()->map(fn ($j) => [
                    $j['name'], $j['datum']->format('d.m.Y'), $j['anzahl'], $j['teilnehmer'], $j['runden'], $j['km'], $e($j['brutto']), $j['sponsoren'], $e($j['bezahlt']),
                ])->all(),
            ],
            default => abort(404),
        };
    }
}
