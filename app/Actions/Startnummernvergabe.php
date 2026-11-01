<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Startnummer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Vergibt Startnummern: bevorzugt die kleinste freie Nummer aus dem Pool,
 * bei leerem Pool die nächste Nummer nach der höchsten vergebenen.
 */
class Startnummernvergabe
{
    public function __construct(private readonly Event $event) {}

    public function naechsteFreie(): int
    {
        $ausPool = Startnummer::forEvent($this->event)
            ->whereNotIn('startnummer', Laeufer::forEvent($this->event)->withTrashed()->whereNotNull('startnummer')->select('startnummer'))
            ->min('startnummer');

        if ($ausPool !== null) {
            return (int) $ausPool;
        }

        return (int) Laeufer::forEvent($this->event)->withTrashed()->max('startnummer') + 1;
    }

    public function istFrei(int $nummer, ?Laeufer $ausser = null): bool
    {
        return Laeufer::forEvent($this->event)->withTrashed()
            ->where('startnummer', $nummer)
            ->when($ausser, fn ($q) => $q->whereKeyNot($ausser->id))
            ->doesntExist();
    }

    /**
     * Weist dem Läufer eine Startnummer zu (Wunschnummer oder nächste freie) und entfernt sie aus dem Pool.
     */
    public function vergeben(Laeufer $laeufer, ?int $wunsch = null): int
    {
        return DB::transaction(function () use ($laeufer, $wunsch) {
            // Mehrere Anmeldetische gleichzeitig: Vergabe pro Veranstaltung nacheinander abarbeiten,
            // sonst erhalten zwei Läufer dieselbe Nummer (Sperre hält bis zum Ende der äußeren Transaktion)
            Event::whereKey($this->event->id)->lockForUpdate()->first();

            return $this->zuweisen($laeufer, $wunsch);
        });
    }

    private function zuweisen(Laeufer $laeufer, ?int $wunsch): int
    {
        if ($wunsch !== null) {
            if (! $this->istFrei($wunsch, $laeufer)) {
                throw ValidationException::withMessages(['startnummer' => "Die Startnummer $wunsch ist bereits vergeben."]);
            }
            $nummer = $wunsch;
        } else {
            $nummer = $this->naechsteFreie();
        }

        $alt = $laeufer->startnummer;
        $laeufer->startnummer = $nummer;
        $laeufer->save();

        Startnummer::forEvent($this->event)->where('startnummer', $nummer)->delete();

        if ($alt !== null && $alt !== $nummer) {
            $this->freigeben($alt);
        }

        return $nummer;
    }

    /** Gibt eine Nummer zurück in den Pool. */
    public function freigeben(int $nummer): void
    {
        Startnummer::forEvent($this->event)->firstOrCreate(['event_id' => $this->event->id, 'startnummer' => $nummer]);
    }
}
