<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Übernimmt Läufer aus einer früheren Veranstaltung in das aktuelle Event
 * (neue Startnummer, Runden 0, Klasse entsprechend hochgestuft).
 */
class LaeuferUebernehmen
{
    public function __construct(private readonly Event $event) {}

    /**
     * @param  Collection<int, Laeufer>  $alteLaeufer
     * @return Collection<int, Laeufer>
     */
    public function ausfuehren(Collection $alteLaeufer, User $besitzer): Collection
    {
        $klassen = Schulklasse::forEvent($this->event)->get();
        $vergabe = new Startnummernvergabe($this->event);

        return DB::transaction(fn () => $alteLaeufer->map(function (Laeufer $alt) use ($klassen, $vergabe, $besitzer) {
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

            $vergabe->vergeben($neu);

            return $neu;
        }));
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
