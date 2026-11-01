<?php

namespace App\Actions;

use App\Http\Controllers\MitverwaltungController;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\User;
use App\Services\Dubletten;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Übernimmt Läufer aus einer früheren Veranstaltung in das aktuelle Event
 * (neue Startnummer, Runden 0, Klasse entsprechend hochgestuft).
 */
class LaeuferUebernehmen
{
    /** @var Collection<int, Laeufer> bereits von einem anderen Konto angemeldete Läufer (Mitverwaltung angefragt) */
    public Collection $bereitsAngemeldet;

    public function __construct(private readonly Event $event)
    {
        $this->bereitsAngemeldet = collect();
    }

    /**
     * @param  Collection<int, Laeufer>  $alteLaeufer
     * @return Collection<int, Laeufer>
     */
    public function ausfuehren(Collection $alteLaeufer, User $besitzer): Collection
    {
        $klassen = Schulklasse::forEvent($this->event)->get();
        $vergabe = new Startnummernvergabe($this->event);

        return DB::transaction(fn () => $alteLaeufer->map(function (Laeufer $alt) use ($klassen, $vergabe, $besitzer) {
            $vorhanden = Dubletten::laeuferKandidaten($this->event, $alt->vorname, $alt->nachname, $alt->geburtsdatum)
                ->firstWhere('grad', Dubletten::SICHER)['laeufer'] ?? null;

            if ($vorhanden) {
                if (! $vorhanden->istVerwaltetVon($besitzer)) {
                    MitverwaltungController::anfrageStellen($vorhanden, $besitzer);
                    $this->bereitsAngemeldet->push($vorhanden);
                }

                return null;
            }

            $neu = new Laeufer([
                'vorname' => $alt->vorname,
                'nachname' => $alt->nachname,
                'geburtsdatum' => $alt->geburtsdatum,
                'email' => $alt->email,
                'geschlecht' => $alt->geschlecht,
                'kategorie' => $alt->kategorie,
            ]);
            $neu->event_id = $this->event->id;
            $neu->verwaltet_von = $alt->verwaltet_von ?? $besitzer->id;
            $neu->schulklasse_id = $this->nachfolgeKlasse($alt, $klassen)?->id;
            $neu->save();

            // Mitverwalter (z. B. zweiter Elternteil) mitnehmen – den ausführenden Benutzer nur, wenn er den
            // Läufer schon verwaltet hat (das Orga-Team wird beim Übernehmen nicht zum Mitverwalter)
            $mitverwalter = $alt->mitverwalter()->pluck('users.id')
                ->when($alt->istVerwaltetVon($besitzer), fn ($ids) => $ids->push($besitzer->id))
                ->reject(fn ($id) => (int) $id === (int) $neu->verwaltet_von)
                ->unique();
            $neu->mitverwalter()->sync($mitverwalter);

            $vergabe->vergeben($neu);

            return $neu;
        })->filter()->values());
    }

    /**
     * Sucht die Klasse im neuen Event: „5a“ (2025) wird bei einem Lauf 2027 zu „7a“.
     *
     * @param  Collection<int, Schulklasse>  $klassen
     */
    public function nachfolgeKlasse(Laeufer $alt, Collection $klassen): ?Schulklasse
    {
        $alteKlasse = $alt->schulklasse_id ? Schulklasse::withoutGlobalScope('event')->find($alt->schulklasse_id) : null;
        $altesEvent = Event::find($alt->event_id);

        if (! $alteKlasse || ! $altesEvent || ! preg_match('/^(\d+)\s*(.*)$/u', $alteKlasse->name, $m)) {
            return null;
        }

        $jahre = $this->event->jahr() - $altesEvent->jahr();
        $neuerName = ((int) $m[1] + $jahre).$m[2];

        return $klassen->first(fn (Schulklasse $k) => mb_strtolower(str_replace(' ', '', $k->name)) === mb_strtolower(str_replace(' ', '', $neuerName)));
    }
}
