<?php

namespace App\Jobs;

use App\Enums\MailTyp;
use App\Mail\SponsorZahlungsaufforderung;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\SponsorMail;
use App\Services\SponsorAbrechnung;
use App\Services\SponsorKonto;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Versendet Zahlungsaufforderung bzw. Erinnerung an einen Sponsor und protokolliert sie.
 *
 * Läuft über die Warteschlange, damit der Massenversand nicht am Zeitlimit des Webservers scheitert.
 * Jeder Auftrag prüft unmittelbar vor dem Versand erneut, ob die Mail noch fällig ist – ein doppelt
 * abgeschicktes Formular oder ein abgebrochener und neu gestarteter Versand erzeugt so keine doppelten Mails.
 */
class SponsorMailVersenden implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> Wartezeit in Sekunden vor dem 2. und 3. Versuch (z. B. bei Störungen des Mailservers) */
    public array $backoff = [60, 300];

    /** Mindestabstand zwischen zwei Mails desselben Typs an denselben Sponsor beim Massenversand. */
    public const SPERRE_STUNDEN = 24;

    /**
     * @param  bool  $erneut  ausdrücklich erneut senden (Einzelversand), auch wenn die Mail schon verschickt wurde
     */
    public function __construct(public Sponsor $sponsor, public Event $event, public MailTyp $typ, public bool $erneut = false) {}

    public function uniqueId(): string
    {
        return $this->sponsor->id.'-'.$this->event->id.'-'.$this->typ->value;
    }

    /** Ist die Mail für den Sponsor (noch) fällig? Wird vor dem Einreihen und vor dem Versand geprüft. */
    public static function faellig(Sponsor $sponsor, Event $event, MailTyp $typ, bool $erneut = false, ?SponsorKonto $konto = null): bool
    {
        $konto ??= SponsorAbrechnung::fuer($sponsor, $event)->konto;

        // Keine Aufforderung/Erinnerung an Sponsoren ohne E-Mail, ohne Spende oder ohne offenen Betrag
        if (blank($sponsor->email) || $konto->sollCent === 0 || $konto->offenCent() <= 0) {
            return false;
        }

        if ($erneut) {
            return true;
        }

        $bisher = SponsorMail::forEvent($event)->where('sponsor_id', $sponsor->id)->where('typ', $typ);

        // Die Zahlungsaufforderung geht nur einmal raus, Erinnerungen höchstens einmal pro Tag
        return $typ === MailTyp::Aufforderung
            ? $bisher->doesntExist()
            : $bisher->where('gesendet_am', '>', now()->subHours(self::SPERRE_STUNDEN))->doesntExist();
    }

    public function handle(): void
    {
        $konto = SponsorAbrechnung::fuer($this->sponsor, $this->event)->konto;

        if (! self::faellig($this->sponsor, $this->event, $this->typ, $this->erneut, $konto)) {
            return;
        }

        Mail::to($this->sponsor->email)->sendNow(new SponsorZahlungsaufforderung($this->sponsor, $this->event, $this->typ));

        $protokoll = new SponsorMail([
            'sponsor_id' => $this->sponsor->id,
            'typ' => $this->typ,
            'betrag' => $konto->offenCent() / 100,
            'gesendet_am' => now(),
        ]);
        $protokoll->event_id = $this->event->id;
        $protokoll->save();
    }
}
