<?php

namespace App\Http\Controllers;

use App\Actions\Startnummernvergabe;
use App\Models\Laeufer;
use App\Models\Sponsor;
use Illuminate\Http\Request;

/**
 * JSON-Endpunkte für Suchfelder (Schnellerfassung, Rundeneingabe).
 */
class SucheController extends Controller
{
    public function laeufer(Request $request)
    {
        $user = $request->user();
        $q = trim((string) $request->input('q'));

        if (mb_strlen($q) < 1) {
            return [];
        }

        $teile = preg_split('/\s+/', $q);

        return Laeufer::query()
            ->with(['schulklasse', 'team'])
            ->unless($user->canAny(['edit laeufer', 'schnellerfassung', 'edit runden']), fn ($b) => $b->verwaltetVon($user))
            ->where(fn ($b) => $b->where('startnummer', ctype_digit($q) ? (int) $q : -1)
                ->orWhere('nachname', 'like', "%$q%")
                ->orWhere('vorname', 'like', "%$q%")
                ->when(count($teile) > 1, fn ($b) => $b->orWhere(fn ($b) => $b
                    ->where('vorname', 'like', '%'.$teile[0].'%')
                    ->where('nachname', 'like', '%'.end($teile).'%'))))
            ->orderBy('nachname')
            ->limit(10)
            ->get()
            ->map(fn (Laeufer $l) => $this->laeuferJson($l));
    }

    public function sponsoren(Request $request)
    {
        $user = $request->user();
        $q = trim((string) $request->input('q'));

        abort_unless($user->canAny(['edit sponsoren', 'schnellerfassung', 'manage abrechnung']), 403);

        if (mb_strlen($q) < 2) {
            return [];
        }

        return Sponsor::query()
            ->where(fn ($b) => $b->where('nachname', 'like', "%$q%")
                ->orWhere('vorname', 'like', "%$q%")
                ->orWhere('firmenname', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%"))
            ->orderBy('nachname')
            ->limit(10)
            ->get()
            ->map(fn (Sponsor $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'ort' => trim($s->plz.' '.$s->ort),
            ]);
    }

    public function startnummer(Request $request, int $nummer)
    {
        abort_unless($request->user()->canAny(['edit laeufer', 'schnellerfassung', 'edit runden', 'edit startnummer']), 403);

        $laeufer = Laeufer::with(['schulklasse', 'team'])->where('startnummer', $nummer)->first();

        return [
            'nummer' => $nummer,
            'frei' => $laeufer === null && (new Startnummernvergabe($this->event()))->istFrei($nummer),
            'laeufer' => $laeufer ? $this->laeuferJson($laeufer) : null,
        ];
    }

    private function laeuferJson(Laeufer $l): array
    {
        return [
            'id' => $l->id,
            'name' => $l->name,
            'startnummer' => $l->startnummer,
            'klasse' => $l->schulklasse?->name,
            'team' => $l->team?->name,
            'runden' => $l->runden,
            'runden_manuell' => $l->runden_manuell,
            'kategorie' => $l->kategorie?->label(),
        ];
    }
}
