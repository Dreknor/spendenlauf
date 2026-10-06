<?php

namespace App\Http\Controllers;

use App\Exports\TabellenExport;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Services\SpendenRechner;
use App\Services\Statistik;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function laeufer()
    {
        $event = $this->event();
        $bilanz = SpendenRechner::fuer($event)->bilanz();

        $zeilen = Laeufer::with(['schulklasse', 'team', 'besitzer'])->orderBy('startnummer')->get()->map(fn (Laeufer $l) => [
            $l->startnummer, $l->nachname, $l->vorname, $l->geburtsdatum?->format('d.m.Y'), $l->geschlecht, $l->kategorie?->label(),
            $l->schulklasse?->name, $l->team?->name, $l->runden, round($bilanz->laeuferSpende($l->id) / 100, 2),
            $l->besitzer?->name, $l->besitzer?->email,
        ])->all();

        return Excel::download(new TabellenExport('Läufer', [
            'Startnummer', 'Nachname', 'Vorname', 'Geburtsdatum', 'Geschlecht', 'Kategorie', 'Klasse', 'Team', 'Runden', 'Spenden (€)', 'Angemeldet von', 'E-Mail',
        ], $zeilen), 'laeufer-'.$event->jahr().'.xlsx');
    }

    public function sponsoren()
    {
        $event = $this->event();
        $bilanz = SpendenRechner::fuer($event)->bilanz();

        $zeilen = Sponsor::whereIn('id', array_keys($bilanz->sponsoren))->orderBy('nachname')->get()->map(fn (Sponsor $s) => [
            $s->id, $s->anrede, $s->firmenname, $s->vorname, $s->nachname, $s->strasse, $s->plz, $s->ort, $s->email, $s->telefon,
            $bilanz->sponsoren[$s->id]['sponsorings'], round($bilanz->sponsorSoll($s->id) / 100, 2), round($bilanz->sponsorIst($s->id) / 100, 2),
            $event->verwendungszweck($s),
        ])->all();

        return Excel::download(new TabellenExport('Sponsoren', [
            'Nr.', 'Anrede', 'Firma', 'Vorname', 'Nachname', 'Straße', 'PLZ', 'Ort', 'E-Mail', 'Telefon', 'Sponsorings', 'Soll (€)', 'Bezahlt (€)', 'Verwendungszweck',
        ], $zeilen), 'sponsoren-'.$event->jahr().'.xlsx');
    }

    /** Verteilung auf Projekte, soziale Abgabe und Klassenkassen. */
    public function projekte()
    {
        $event = $this->event();
        $s = Statistik::fuer($event);

        $zeilen = $s->projekte()->map(fn ($p) => [$p['label'], round($p['betrag'] / 100, 2)])->all();
        $zeilen[] = ['Summe', round($s->bilanz()->bruttoCent / 100, 2)];
        $zeilen[] = [];
        $zeilen[] = ['Klassenkassen im Detail', ''];
        foreach ($s->klassen() as $k) {
            $zeilen[] = ['Klasse '.$k['name'], round($k['kasse'] / 100, 2)];
        }

        return Excel::download(new TabellenExport('Verteilung', ['Empfänger', 'Betrag (€)'], $zeilen), 'verteilung-'.$event->jahr().'.xlsx');
    }
}
