<?php

namespace App\Http\Controllers;

use App\Enums\MailTyp;
use App\Enums\ZahlungsArt;
use App\Enums\ZahlungsStatus;
use App\Exports\TabellenExport;
use App\Mail\SponsorZahlungsaufforderung;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorMail;
use App\Services\SpendenRechner;
use App\Services\SponsorAbrechnung;
use App\Services\SponsorKonto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AbrechnungController extends Controller
{
    public function index(Request $request)
    {
        $event = $this->event();
        $konten = $this->konten($event);

        $gefiltert = $konten
            ->when($request->filled('status'), fn ($c) => $c->filter(fn ($k) => $k['status']->value === $request->input('status')))
            ->when($request->filled('q'), function ($c) use ($request, $event) {
                $q = mb_strtolower($request->input('q'));

                return $c->filter(fn ($k) => str_contains(mb_strtolower($k['sponsor']->name.' '.$k['sponsor']->email.' '.$event->verwendungszweck($k['sponsor'])), $q)
                    || (string) $k['sponsor']->id === $q);
            })
            ->when($request->input('mail') === 'ohne', fn ($c) => $c->filter(fn ($k) => $k['aufforderung'] === null && filled($k['sponsor']->email)))
            ->when($request->input('mail') === 'keine_adresse', fn ($c) => $c->filter(fn ($k) => blank($k['sponsor']->email)));

        $sort = $request->input('sort', 'name');
        $gefiltert = match ($sort) {
            'soll' => $gefiltert->sortByDesc(fn ($k) => $k['konto']->sollCent),
            'offen' => $gefiltert->sortByDesc(fn ($k) => $k['konto']->offenCent()),
            default => $gefiltert->sortBy(fn ($k) => mb_strtolower($k['sponsor']->nachname ?: $k['sponsor']->firmenname)),
        };

        $bilanz = SpendenRechner::fuer($event)->bilanz();

        return view('abrechnung.index', [
            'event' => $event,
            'konten' => $gefiltert->values(),
            'anzahl' => $konten->countBy(fn ($k) => $k['status']->value),
            'bilanz' => $bilanz,
            'ohneMail' => $konten->filter(fn ($k) => blank($k['sponsor']->email))->count(),
            'ohneAufforderung' => $konten->filter(fn ($k) => $k['aufforderung'] === null && filled($k['sponsor']->email))->count(),
        ]);
    }

    public function show(Sponsor $sponsor)
    {
        $event = $this->event();

        return view('abrechnung.show', [
            'event' => $event,
            'a' => SponsorAbrechnung::fuer($sponsor, $event),
            'mails' => SponsorMail::where('sponsor_id', $sponsor->id)->latest('gesendet_am')->get(),
            'arten' => ZahlungsArt::options(),
        ]);
    }

    /** Vorschau der Empfänger für Zahlungsaufforderung oder Erinnerung. */
    public function mails(Request $request)
    {
        $event = $this->event();
        $typ = MailTyp::tryFrom($request->input('typ', 'aufforderung')) ?? MailTyp::Aufforderung;
        $tage = max(0, $request->integer('tage', 14));

        return view('abrechnung.mails', [
            'event' => $event,
            'typ' => $typ,
            'tage' => $tage,
            'empfaenger' => $this->empfaenger($event, $typ, $tage),
        ]);
    }

    public function mailsSenden(Request $request)
    {
        $event = $this->event();
        $daten = $request->validate([
            'typ' => ['required', Rule::enum(MailTyp::class)->only([MailTyp::Aufforderung, MailTyp::Erinnerung])],
            'sponsoren' => ['required', 'array'],
            'sponsoren.*' => ['integer'],
        ]);

        $typ = MailTyp::from($daten['typ']);
        $anzahl = 0;
        foreach (Sponsor::whereIn('id', $daten['sponsoren'])->get() as $sponsor) {
            $anzahl += $this->senden($sponsor, $event, $typ) ? 1 : 0;
        }

        return $this->erfolg("$anzahl {$typ->label()}(en) wurden versandt.", route('abrechnung.index'));
    }

    public function mailSenden(Request $request, Sponsor $sponsor)
    {
        $typ = MailTyp::tryFrom($request->input('typ', 'aufforderung')) ?? MailTyp::Aufforderung;

        if (! $this->senden($sponsor, $this->event(), $typ)) {
            return $this->fehler('Der Sponsor hat keine E-Mail-Adresse oder keine offene Spende.');
        }

        return $this->erfolg($typ->label().' wurde an '.$sponsor->email.' versandt.');
    }

    public function nachweis(Sponsor $sponsor)
    {
        $abrechnung = SponsorAbrechnung::fuer($sponsor, $this->event());

        if (! $abrechnung->konto->istBezahlt()) {
            return $this->fehler('Ein Nachweis kann erst nach vollständiger Zahlung erstellt werden.');
        }

        return Pdf::loadView('pdf.nachweis', ['a' => $abrechnung])->stream('Spendennachweis-'.$sponsor->id.'.pdf');
    }

    public function export(string $art)
    {
        $event = $this->event();
        $konten = $this->konten($event);
        $e = fn (int $c) => round($c / 100, 2);

        $konten = match ($art) {
            'offen' => $konten->filter(fn ($k) => in_array($k['status'], [ZahlungsStatus::Offen, ZahlungsStatus::Teilweise])),
            'grossspenden' => $konten->filter(fn ($k) => $k['konto']->istCent > config('spendenlauf.nachweis_grenze_cent')),
            'alle' => $konten,
            default => abort(404),
        };

        $zeilen = $konten->map(fn ($k) => [
            $k['sponsor']->id, $k['sponsor']->anrede, $k['sponsor']->firmenname, $k['sponsor']->vorname, $k['sponsor']->nachname,
            $k['sponsor']->strasse, $k['sponsor']->plz, $k['sponsor']->ort, $k['sponsor']->email,
            $event->verwendungszweck($k['sponsor']), $e($k['konto']->sollCent), $e($k['konto']->istCent), $e($k['konto']->offenCent()),
            $k['status']->label(), $k['aufforderung']?->format('d.m.Y'),
        ])->values()->all();

        $kopf = ['Nr.', 'Anrede', 'Firma', 'Vorname', 'Nachname', 'Straße', 'PLZ', 'Ort', 'E-Mail', 'Verwendungszweck', 'Soll (€)', 'Bezahlt (€)', 'Offen (€)', 'Status', 'Aufforderung am'];

        return Excel::download(new TabellenExport('Abrechnung '.$art, $kopf, $zeilen), "Abrechnung-$art-".now()->format('Y-m-d').'.xlsx');
    }

    // ------------------------------------------------------------------

    /**
     * Alle Sponsoren mit Spenden in der Veranstaltung inkl. Kontostand.
     *
     * @return Collection<int, array{sponsor: Sponsor, konto: SponsorKonto, status: ZahlungsStatus, aufforderung: ?CarbonInterface, erinnerung: ?CarbonInterface}>
     */
    private function konten(Event $event): Collection
    {
        $bilanz = SpendenRechner::fuer($event)->bilanz();
        $ids = array_unique(array_merge(array_keys($bilanz->sponsoren), array_keys($bilanz->zahlungen)));
        $mails = SponsorMail::whereIn('sponsor_id', $ids)->get()->groupBy('sponsor_id');

        return Sponsor::whereIn('id', $ids)->get()->map(function (Sponsor $sponsor) use ($bilanz, $mails) {
            $konto = new SponsorKonto($bilanz->sponsorSoll($sponsor->id), $bilanz->sponsorIst($sponsor->id));
            $eigene = $mails->get($sponsor->id, collect());

            return [
                'sponsor' => $sponsor,
                'konto' => $konto,
                'status' => $konto->status(),
                'aufforderung' => $eigene->where('typ', MailTyp::Aufforderung)->max('gesendet_am'),
                'erinnerung' => $eigene->where('typ', MailTyp::Erinnerung)->max('gesendet_am'),
            ];
        });
    }

    private function empfaenger(Event $event, MailTyp $typ, int $tage): Collection
    {
        return $this->konten($event)
            ->filter(fn ($k) => filled($k['sponsor']->email) && $k['konto']->offenCent() > 0)
            ->filter(function ($k) use ($typ, $tage) {
                if ($typ === MailTyp::Aufforderung) {
                    return $k['aufforderung'] === null;
                }
                $letzte = collect([$k['aufforderung'], $k['erinnerung']])->filter()->max();

                return $letzte !== null && $letzte->lte(now()->subDays($tage));
            })
            ->sortBy(fn ($k) => mb_strtolower($k['sponsor']->name))
            ->values();
    }

    private function senden(Sponsor $sponsor, Event $event, MailTyp $typ): bool
    {
        $abrechnung = SponsorAbrechnung::fuer($sponsor, $event);

        // Keine Aufforderung/Erinnerung an Sponsoren ohne Spende oder ohne offenen Betrag
        if (blank($sponsor->email) || $abrechnung->konto->sollCent === 0 || $abrechnung->konto->offenCent() <= 0) {
            return false;
        }

        Mail::to($sponsor->email)->queue(new SponsorZahlungsaufforderung($sponsor, $event, $typ));

        SponsorMail::create([
            'sponsor_id' => $sponsor->id,
            'typ' => $typ,
            'betrag' => $abrechnung->konto->offenCent() / 100,
            'gesendet_am' => now(),
        ]);

        return true;
    }
}
