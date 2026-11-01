<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
use App\Models\MitverwaltungEinladung;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\SponsorMail;
use App\Models\Zahlung;
use App\Services\SpendenRechner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Führt doppelt angelegte Datensätze zusammen. Der zweite Datensatz wird danach gelöscht,
 * alle Spenden, Zahlungen und Zuordnungen wandern zum behaltenen Datensatz.
 */
class Zusammenfuehren
{
    /** Felder, die beim behaltenen Datensatz ergänzt werden, falls sie dort leer sind. */
    private const LAEUFER_FELDER = ['geburtsdatum', 'geschlecht', 'email', 'kategorie', 'schulklasse_id', 'team_id'];

    private const SPONSOR_FELDER = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon'];

    /** @var list<string> Hinweise zum letzten Zusammenführen von Läufern (Startnummern, nicht übernommene Runden) */
    public array $hinweise = [];

    /**
     * Läufer zusammenführen. Startnummer und Runden gehören zusammen, weil die Zeitmessung pro Startnummer
     * zählt: Übernommen werden Nummer und Runden des Läufers, dessen Nummer behalten wird ($startnummer,
     * sonst die Nummer mit den meisten Runden). Die andere Nummer kommt zurück in den Pool – außer es wurden
     * darauf schon Runden gezählt: Dann bleibt sie gesperrt, damit ein neuer Läufer nicht diese Runden erbt.
     */
    public function laeufer(Laeufer $behalten, Laeufer $entfernen, ?int $startnummer = null): Laeufer
    {
        if ($behalten->is($entfernen) || $behalten->event_id !== $entfernen->event_id) {
            throw new InvalidArgumentException('Es können nur zwei verschiedene Läufer derselben Veranstaltung zusammengeführt werden.');
        }

        DB::transaction(function () use ($behalten, $entfernen, $startnummer) {
            // Nicht gleichzeitig mit Startnummernvergabe und Runden-Import (beide sperren dieselben Zeilen)
            Event::whereKey($behalten->event_id)->lockForUpdate()->first();
            Laeufer::withoutGlobalScope('event')->whereKey([$behalten->id, $entfernen->id])->lockForUpdate()->get();
            $behalten->refresh();
            $entfernen->refresh();

            $quelle = self::nummerQuelle($behalten, $entfernen, $startnummer);
            $andere = $quelle->is($behalten) ? $entfernen : $behalten;
            $verworfen = $andere->startnummer;
            $sperren = $verworfen !== null && $andere->runden > 0;

            // Runden hängen an der Startnummer; hat nur einer eine Nummer, zählen die meisten (z. B. manuell erfassten) Runden
            $rundenQuelle = $verworfen === null && $andere->runden > $quelle->runden ? $andere : $quelle;
            $rundenVerworfen = ($rundenQuelle->is($quelle) ? $andere : $quelle)->runden;

            foreach (self::LAEUFER_FELDER as $feld) {
                if (blank($behalten->{$feld}) && filled($entfernen->{$feld})) {
                    $behalten->{$feld} = $entfernen->{$feld};
                }
            }

            $behalten->runden = $rundenQuelle->runden;
            $behalten->runden_manuell = $rundenQuelle->runden_manuell;
            $behalten->runden_aktualisiert_am = $rundenQuelle->runden_aktualisiert_am;

            // Nummern umhängen (eindeutig je Veranstaltung): erst beim entfernten Läufer freimachen
            $nummer = $quelle->startnummer;
            $entfernen->startnummer = null;
            $entfernen->saveQuietly();
            $behalten->startnummer = $nummer;

            Sponsoring::forEvent($behalten->event_id)
                ->where('sponsorable_type', $entfernen->getMorphClass())
                ->where('sponsorable_id', $entfernen->id)
                ->update(['sponsorable_id' => $behalten->id]);

            // Beide anmeldenden Konten verwalten den Läufer weiter
            $verwalter = $entfernen->mitverwalter()->pluck('users.id')
                ->push($entfernen->verwaltet_von)
                ->filter()
                ->reject(fn ($id) => (int) $id === (int) $behalten->verwaltet_von)
                ->unique();
            $behalten->mitverwalter()->syncWithoutDetaching($verwalter->all());

            MitverwaltungAnfrage::where('laeufer_id', $entfernen->id)->delete();
            MitverwaltungEinladung::where('laeufer_id', $entfernen->id)->delete();

            $behalten->save();

            // Gesperrte Nummer bleibt beim gelöschten Läufer stehen – die Vergabe zählt gelöschte Läufer mit
            if ($sperren) {
                $entfernen->startnummer = $verworfen;
                $entfernen->saveQuietly();
            } elseif ($verworfen !== null) {
                (new Startnummernvergabe($behalten->event))->freigeben($verworfen);
            }
            $entfernen->delete();

            $this->hinweise = array_values(array_filter([
                $sperren ? "Startnummer $verworfen bleibt gesperrt, weil darauf schon Runden gezählt wurden." : null,
                ! $sperren && $verworfen !== null ? "Startnummer $verworfen ist wieder frei." : null,
                $rundenVerworfen > 0 ? "$rundenVerworfen Runden des anderen Eintrags wurden nicht übernommen." : null,
            ]));

            Log::info('Läufer zusammengeführt', [
                'behalten' => $behalten->id, 'entfernt' => $entfernen->id, 'startnummer' => $nummer,
                'startnummer_verworfen' => $verworfen, 'gesperrt' => $sperren, 'runden_verworfen' => $rundenVerworfen,
            ]);
        });

        SpendenRechner::vergessen();

        return $behalten->fresh();
    }

    /**
     * Läufer, dessen Startnummer (und damit dessen Runden) erhalten bleibt: die gewünschte Nummer,
     * sonst die mit den meisten Runden, bei Gleichstand die des behaltenen Läufers.
     */
    public static function nummerQuelle(Laeufer $behalten, Laeufer $entfernen, ?int $startnummer = null): Laeufer
    {
        if ($startnummer !== null) {
            return match ($startnummer) {
                $behalten->startnummer => $behalten,
                $entfernen->startnummer => $entfernen,
                default => throw new InvalidArgumentException("Die Startnummer $startnummer gehört zu keinem der beiden Läufer."),
            };
        }

        if (($behalten->startnummer === null) !== ($entfernen->startnummer === null)) {
            return $behalten->startnummer !== null ? $behalten : $entfernen;
        }

        return $entfernen->runden > $behalten->runden ? $entfernen : $behalten;
    }

    public function sponsor(Sponsor $behalten, Sponsor $entfernen): Sponsor
    {
        if ($behalten->is($entfernen)) {
            throw new InvalidArgumentException('Ein Sponsor kann nicht mit sich selbst zusammengeführt werden.');
        }

        DB::transaction(function () use ($behalten, $entfernen) {
            $email = $entfernen->email;
            foreach (self::SPONSOR_FELDER as $feld) {
                if (blank($behalten->{$feld}) && filled($entfernen->{$feld}) && $feld !== 'email') {
                    $behalten->{$feld} = $entfernen->{$feld};
                }
            }

            // Spenden, Zahlungen und Mails aller Veranstaltungen übernehmen
            Sponsoring::withoutGlobalScope('event')->where('sponsor_id', $entfernen->id)->update(['sponsor_id' => $behalten->id]);
            Zahlung::withoutGlobalScope('event')->where('sponsor_id', $entfernen->id)->update(['sponsor_id' => $behalten->id]);
            SponsorMail::withoutGlobalScope('event')->where('sponsor_id', $entfernen->id)->update(['sponsor_id' => $behalten->id]);

            $behalten->users()->syncWithoutDetaching($entfernen->users()->pluck('users.id')->all());
            $entfernen->users()->detach();
            $entfernen->delete();

            // E-Mail ist eindeutig: erst nach dem Löschen übernehmen
            if (blank($behalten->email) && filled($email)) {
                $behalten->email = $email;
            }
            $behalten->save();

            Log::info('Sponsoren zusammengeführt', ['behalten' => $behalten->id, 'entfernt' => $entfernen->id]);
        });

        SpendenRechner::vergessen();

        return $behalten->fresh();
    }
}
