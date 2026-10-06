<?php

namespace App\Http\Controllers;

use App\Mail\MitverwaltungAngefragt;
use App\Mail\MitverwaltungEntschieden;
use App\Models\Laeufer;
use App\Models\MitverwaltungAnfrage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Mehrere Benutzer (z. B. beide Elternteile) können einen Läufer gemeinsam verwalten.
 * Wer einen bereits angemeldeten Läufer findet, fragt an; der anmeldende Elternteil
 * oder das Orga-Team bestätigt. Das Orga-Team kann auch direkt zuordnen.
 */
class MitverwaltungController extends Controller
{
    public function anfragen(Request $request, Laeufer $laeufer)
    {
        $user = $request->user();

        if ($laeufer->istVerwaltetVon($user)) {
            return $this->erfolg("Sie verwalten {$laeufer->name} bereits.", route('laeufer.edit', $laeufer));
        }

        $anfrage = self::anfrageStellen($laeufer, $user);

        return $this->erfolg($anfrage->wasRecentlyCreated
            ? "Ihre Anfrage für {$laeufer->vorname} wurde gesendet. Sobald sie bestätigt ist, finden Sie den Läufer unter „Meine Läufer“."
            : 'Sie haben bereits eine Anfrage gestellt. Sie wird bestätigt, sobald der anmeldende Elternteil oder das Orga-Team zustimmt.', route('home'));
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

    /** Direkte Zuordnung eines weiteren Kontos (über dessen E-Mail-Adresse). */
    public function hinzufuegen(Request $request, Laeufer $laeufer)
    {
        $this->authorize('update', $laeufer);

        $daten = $request->validate(['email' => ['required', 'email']], [], ['email' => 'E-Mail-Adresse']);
        $user = User::where('email', $daten['email'])->first();

        if (! $user) {
            return $this->fehler('Zu dieser E-Mail-Adresse gibt es kein Benutzerkonto. Die Person muss sich zuerst registrieren.');
        }
        if ($laeufer->istVerwaltetVon($user)) {
            return $this->fehler("{$user->name} verwaltet {$laeufer->vorname} bereits.");
        }

        $laeufer->mitverwalter()->syncWithoutDetaching([$user->id]);
        $laeufer->mitverwaltungAnfragen()->where('user_id', $user->id)->where('status', MitverwaltungAnfrage::OFFEN)
            ->update(['status' => MitverwaltungAnfrage::ANGENOMMEN, 'entschieden_von' => $request->user()->id, 'entschieden_am' => now()]);

        return $this->erfolg("{$user->name} verwaltet {$laeufer->vorname} jetzt mit.");
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
}
