<?php

namespace App\Http\Controllers;

use App\Mail\MitverwaltungAngefragt;
use App\Mail\MitverwaltungEingeladen;
use App\Mail\MitverwaltungEntschieden;
use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
use App\Models\MitverwaltungEinladung;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Mehrere Benutzer (z. B. beide Elternteile) können einen Läufer gemeinsam verwalten.
 * Zustande kommt das immer mit Zustimmung beider Seiten:
 * - Wer einen bereits angemeldeten Läufer anmelden will, fragt an; der anmeldende Elternteil bestätigt.
 * - Wer einen Läufer verwaltet, lädt ein weiteres Konto per E-Mail ein; die eingeladene Person nimmt an.
 * Das Orga-Team kann Konten direkt zuordnen und Anfragen bestätigen.
 */
class MitverwaltungController extends Controller
{
    /** Session-Schlüssel: Läufer, die bei der eigenen Anmeldung als „bereits angemeldet“ gefunden wurden. */
    public const KANDIDATEN = 'mitverwaltung_kandidaten';

    public function anfragen(Request $request, Laeufer $laeufer)
    {
        $user = $request->user();

        if ($laeufer->istVerwaltetVon($user)) {
            return $this->erfolg("Sie verwalten {$laeufer->name} bereits.", route('laeufer.edit', $laeufer));
        }

        // Nur für Läufer, auf die die eigene Anmeldung gestoßen ist – sonst könnte jedes Konto
        // beliebige Läufer anfragen und deren Eltern mit Anfragen überhäufen
        if (! in_array($laeufer->id, $request->session()->get(self::KANDIDATEN, []), true)) {
            return $this->fehler('Bitte melden Sie den Läufer zuerst an. Ist er schon angemeldet, können Sie dort die Mitverwaltung anfragen.', route('laeufer.create'));
        }

        $anfrage = self::anfrageStellen($laeufer, $user);

        return $this->erfolg($anfrage->wasRecentlyCreated
            ? "Ihre Anfrage für {$laeufer->vorname} wurde gesendet. Sobald sie bestätigt ist, finden Sie den Läufer unter „Meine Läufer“."
            : 'Sie haben bereits eine Anfrage gestellt. Sie wird bestätigt, sobald der anmeldende Elternteil oder das Orga-Team zustimmt.', route('home'));
    }

    /** Merkt sich Läufer, für die der Benutzer anschließend die Mitverwaltung anfragen darf. */
    public static function kandidatenMerken(Request $request, iterable $laeuferIds): void
    {
        $bisher = $request->session()->get(self::KANDIDATEN, []);
        $request->session()->put(self::KANDIDATEN, array_values(array_unique(array_merge($bisher, array_map('intval', [...$laeuferIds])))));
    }

    /** Legt eine offene Anfrage an (falls noch keine besteht) und benachrichtigt den anmeldenden Elternteil. */
    public static function anfrageStellen(Laeufer $laeufer, User $user): MitverwaltungAnfrage
    {
        $anfrage = MitverwaltungAnfrage::firstOrCreate(
            ['laeufer_id' => $laeufer->id, 'user_id' => $user->id, 'status' => MitverwaltungAnfrage::OFFEN],
        );

        if ($anfrage->wasRecentlyCreated && $laeufer->besitzer && $laeufer->besitzer->isNot($user)) {
            Mail::to($laeufer->besitzer->email)->queue(new MitverwaltungAngefragt($anfrage));
        }

        return $anfrage;
    }

    public function annehmen(Request $request, MitverwaltungAnfrage $anfrage)
    {
        return $this->entscheiden($request, $anfrage, MitverwaltungAnfrage::ANGENOMMEN);
    }

    public function ablehnen(Request $request, MitverwaltungAnfrage $anfrage)
    {
        return $this->entscheiden($request, $anfrage, MitverwaltungAnfrage::ABGELEHNT);
    }

    /**
     * Weiteres Konto hinzufügen: Das Orga-Team ordnet ein vorhandenes Konto direkt zu,
     * alle anderen schicken eine Einladung, die die Person erst annehmen muss.
     */
    public function hinzufuegen(Request $request, Laeufer $laeufer)
    {
        $this->authorize('update', $laeufer);

        $daten = $request->validate(['email' => ['required', 'email', 'max:190']], [], ['email' => 'E-Mail-Adresse']);
        $email = MitverwaltungEinladung::adresse($daten['email']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($request->user()->can('edit laeufer')) {
            if (! $user) {
                return $this->fehler('Zu dieser E-Mail-Adresse gibt es kein Benutzerkonto. Die Person muss sich zuerst registrieren.');
            }
            if ($laeufer->istVerwaltetVon($user)) {
                return $this->fehler("{$user->name} verwaltet {$laeufer->vorname} bereits.");
            }

            $this->zuordnen($laeufer, $user, $request->user());

            return $this->erfolg("{$user->name} verwaltet {$laeufer->vorname} jetzt mit.");
        }

        // Die Antwort verrät bewusst nicht, ob es zu der Adresse ein Konto gibt
        $meldung = "Wir haben eine Einladung an $email geschickt. Sobald die Person sie annimmt, verwaltet sie {$laeufer->vorname} mit.";

        if ($user && $laeufer->istVerwaltetVon($user)) {
            return $this->erfolg($meldung);
        }

        $offen = $laeufer->mitverwaltungEinladungen()->offen()->pluck('email');
        if (! $offen->contains($email)) {
            if ($offen->count() >= MitverwaltungEinladung::MAX_OFFEN) {
                return $this->fehler('Für diesen Läufer sind bereits mehrere Einladungen offen. Bitte ziehen Sie zuerst eine zurück.');
            }

            $einladung = new MitverwaltungEinladung(['laeufer_id' => $laeufer->id, 'email' => $email, 'status' => MitverwaltungAnfrage::OFFEN]);
            $einladung->eingeladen_von = $request->user()->id;
            $einladung->save();

            Mail::to($email)->queue(new MitverwaltungEingeladen($einladung));
        }

        return $this->erfolg($meldung);
    }

    public function einladungAnnehmen(Request $request, MitverwaltungEinladung $einladung)
    {
        return $this->einladungBeantworten($request, $einladung, MitverwaltungAnfrage::ANGENOMMEN);
    }

    public function einladungAblehnen(Request $request, MitverwaltungEinladung $einladung)
    {
        return $this->einladungBeantworten($request, $einladung, MitverwaltungAnfrage::ABGELEHNT);
    }

    public function einladungZurueckziehen(MitverwaltungEinladung $einladung)
    {
        abort_if($einladung->laeufer === null, 404);
        $this->authorize('update', $einladung->laeufer);

        $einladung->delete();

        return $this->erfolg('Die Einladung wurde zurückgezogen.');
    }

    public function entfernen(Request $request, Laeufer $laeufer, User $user)
    {
        // Jeder darf sich selbst entfernen; andere nur mit Bearbeitungsrecht
        if ($request->user()->isNot($user)) {
            $this->authorize('update', $laeufer);
        }

        $laeufer->mitverwalter()->detach($user->id);

        return $request->user()->is($user)
            ? $this->erfolg("Sie verwalten {$laeufer->vorname} nicht mehr mit.", route('laeufer.index'))
            : $this->erfolg("{$user->name} wurde als Mitverwalter entfernt.");
    }

    private function entscheiden(Request $request, MitverwaltungAnfrage $anfrage, string $status)
    {
        $laeufer = $anfrage->laeufer;
        abort_if($laeufer === null, 404);

        $user = $request->user();
        abort_unless($user->can('edit laeufer') || (int) $laeufer->verwaltet_von === (int) $user->id, 403);

        if ($anfrage->status !== MitverwaltungAnfrage::OFFEN) {
            return $this->fehler('Diese Anfrage wurde bereits bearbeitet.');
        }

        $anfrage->forceFill(['status' => $status, 'entschieden_von' => $user->id, 'entschieden_am' => now()])->save();

        if ($status === MitverwaltungAnfrage::ANGENOMMEN) {
            $laeufer->mitverwalter()->syncWithoutDetaching([$anfrage->user_id]);
        }

        Mail::to($anfrage->user->email)->queue(new MitverwaltungEntschieden($anfrage));

        return $this->erfolg($status === MitverwaltungAnfrage::ANGENOMMEN
            ? "{$anfrage->user->name} verwaltet {$laeufer->vorname} jetzt mit."
            : 'Die Anfrage wurde abgelehnt.');
    }

    private function einladungBeantworten(Request $request, MitverwaltungEinladung $einladung, string $status)
    {
        $user = $request->user();
        $laeufer = $einladung->laeufer;

        // Antworten darf nur das (bestätigte) Konto mit der eingeladenen E-Mail-Adresse
        abort_unless($einladung->giltFuer($user), 403);
        abort_if($laeufer === null, 404);

        if (! MitverwaltungEinladung::whereKey($einladung->id)->offen()->exists()) {
            return $this->fehler('Diese Einladung ist nicht mehr gültig.', route('home'));
        }

        $einladung->forceFill(['status' => $status, 'entschieden_am' => now()])->save();

        if ($status === MitverwaltungAnfrage::ABGELEHNT) {
            return $this->erfolg('Die Einladung wurde abgelehnt.', route('home'));
        }

        if (! $laeufer->istVerwaltetVon($user)) {
            $this->zuordnen($laeufer, $user, $user);
        }

        return $this->erfolg("Sie verwalten {$laeufer->vorname} jetzt mit.", route('laeufer.edit', $laeufer));
    }

    private function zuordnen(Laeufer $laeufer, User $user, User $entschiedenVon): void
    {
        $laeufer->mitverwalter()->syncWithoutDetaching([$user->id]);
        $laeufer->mitverwaltungAnfragen()->where('user_id', $user->id)->where('status', MitverwaltungAnfrage::OFFEN)
            ->update(['status' => MitverwaltungAnfrage::ANGENOMMEN, 'entschieden_von' => $entschiedenVon->id, 'entschieden_am' => now()]);
    }
}
