<?php

namespace App\Services;

use App\Enums\ZahlungsStatus;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Models\Zahlung;
use Illuminate\Support\Collection;

/**
 * Aufstellung eines Sponsors für eine Veranstaltung (Mail, Sponsor-Seite, Nachweis).
 */
class SponsorAbrechnung
{
    /** @var Collection<int, array<string, mixed>> */
    public readonly Collection $positionen;

    /** @var Collection<int, Zahlung> */
    public readonly Collection $zahlungen;

    public readonly SponsorKonto $konto;

    public function __construct(public readonly Sponsor $sponsor, public readonly Event $event)
    {
        $rechner = SpendenRechner::fuer($event);
        $laeufer = Laeufer::forEvent($event)->get()->keyBy('id');
        $teams = Teams::forEvent($event)->get()->keyBy('id');

        $this->positionen = Sponsoring::forEvent($event)
            ->where('sponsor_id', $sponsor->id)
            ->with('projects')
            ->get()
            ->map(function (Sponsoring $s) use ($rechner, $laeufer, $teams) {
                $aufteilung = $rechner->aufteilung($s);
                $ziel = $s->istTeamSponsoring() ? $teams->get($s->sponsorable_id) : $laeufer->get($s->sponsorable_id);
                $runden = $s->istTeamSponsoring()
                    ? (int) $laeufer->where('team_id', $s->sponsorable_id)->sum('runden')
                    : (int) ($ziel?->runden ?? 0);

                return [
                    'sponsoring' => $s,
                    'ziel' => $ziel ? ($s->istTeamSponsoring() ? 'Team „'.$ziel->name.'“' : $ziel->name) : '(gelöscht)',
                    'runden' => $runden,
                    'rundenBetrag' => SpendenRechner::cent($s->rundenBetrag),
                    'festBetrag' => SpendenRechner::cent($s->festBetrag),
                    'maxBetrag' => $s->maxBetrag !== null && (float) $s->maxBetrag > 0 ? SpendenRechner::cent($s->maxBetrag) : null,
                    'betrag' => $aufteilung?->brutto ?? 0,
                    'projekte' => $s->projects->pluck('name')->all(),
                ];
            });

        $this->zahlungen = Zahlung::forEvent($event)->where('sponsor_id', $sponsor->id)->orderBy('bezahlt_am')->get();

        $this->konto = new SponsorKonto(
            (int) $this->positionen->sum('betrag'),
            (int) $this->zahlungen->sum(fn (Zahlung $z) => SpendenRechner::cent($z->betrag)),
        );
    }

    public static function fuer(Sponsor $sponsor, Event $event): self
    {
        return new self($sponsor, $event);
    }

    public function status(): ZahlungsStatus
    {
        return $this->konto->status();
    }

    public function verwendungszweck(): string
    {
        return $this->event->verwendungszweck($this->sponsor);
    }

    public function paypalLink(): ?string
    {
        return $this->event->paypalLink($this->konto->offenCent());
    }

    public function portalUrl(): string
    {
        return route('public.sponsor.konto', $this->sponsor->uuid);
    }

    /** Vereinfachter Nachweis möglich: vollständig bezahlt und höchstens 300 €. */
    public function nachweisMoeglich(): bool
    {
        return $this->konto->istBezahlt() && $this->konto->istCent <= config('spendenlauf.nachweis_grenze_cent');
    }
}
