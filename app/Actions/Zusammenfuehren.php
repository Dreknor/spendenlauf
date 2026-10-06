<?php

namespace App\Actions;

use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
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

    public function laeufer(Laeufer $behalten, Laeufer $entfernen): Laeufer
    {
        if ($behalten->is($entfernen) || $behalten->event_id !== $entfernen->event_id) {
            throw new InvalidArgumentException('Es können nur zwei verschiedene Läufer derselben Veranstaltung zusammengeführt werden.');
        }

        DB::transaction(function () use ($behalten, $entfernen) {
            foreach (self::LAEUFER_FELDER as $feld) {
                if (blank($behalten->{$feld}) && filled($entfernen->{$feld})) {
                    $behalten->{$feld} = $entfernen->{$feld};
                }
            }

            // Wer mehr Runden hat, ist vermutlich mit der „richtigen“ Startnummer gelaufen
            if ($entfernen->runden > $behalten->runden) {
                $behalten->runden = $entfernen->runden;
                $behalten->runden_manuell = $entfernen->runden_manuell;
                $behalten->runden_aktualisiert_am = $entfernen->runden_aktualisiert_am;
            }

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

            $nummer = $entfernen->startnummer;
            $entfernen->startnummer = null;
            $entfernen->saveQuietly();
            $entfernen->delete();

            $behalten->save();

            if ($nummer !== null && $nummer !== $behalten->startnummer) {
                (new Startnummernvergabe($behalten->event))->freigeben($nummer);
            }

            Log::info('Läufer zusammengeführt', ['behalten' => $behalten->id, 'entfernt' => $entfernen->id, 'startnummer_frei' => $nummer]);
        });

        SpendenRechner::vergessen();

        return $behalten->fresh();
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
