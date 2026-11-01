<?php

namespace App\Http\Controllers;

use App\Actions\Zusammenfuehren;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Services\Dubletten;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Liste möglicher Dubletten für das Orga-Team, mit Zusammenführen und „keine Dublette“.
 */
class DublettenController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $art = $request->input('art', $user->can('edit laeufer') ? 'laeufer' : 'sponsoren');
        abort_unless($art === 'laeufer' ? $user->can('edit laeufer') : $user->can('edit sponsoren'), 403);

        return view('dubletten.index', [
            'art' => $art,
            'paare' => $art === 'laeufer' ? Dubletten::laeuferPaare($this->event()) : Dubletten::sponsorPaare(),
        ]);
    }

    public function laeufer(Request $request, Zusammenfuehren $aktion)
    {
        abort_unless($request->user()->can('edit laeufer'), 403);

        [$behalten, $entfernen] = $this->paar($request, Laeufer::class);
        $startnummer = $request->validate(['startnummer' => ['nullable', 'integer']])['startnummer'] ?? null;

        try {
            $ergebnis = $aktion->laeufer($behalten, $entfernen, $startnummer !== null ? (int) $startnummer : null);
        } catch (InvalidArgumentException $e) {
            return $this->fehler($e->getMessage());
        }

        $meldung = "Läufer zusammengeführt: {$ergebnis->name} (Startnummer ".($ergebnis->startnummer ?? '–').", {$ergebnis->runden} Runden).";

        return $this->erfolg(trim($meldung.' '.implode(' ', $aktion->hinweise)), route('dubletten.index', ['art' => 'laeufer']));
    }

    public function sponsoren(Request $request, Zusammenfuehren $aktion)
    {
        abort_unless($request->user()->can('edit sponsoren'), 403);

        [$behalten, $entfernen] = $this->paar($request, Sponsor::class);
        $ergebnis = $aktion->sponsor($behalten, $entfernen);

        return $this->erfolg("Sponsoren zusammengeführt: {$ergebnis->name}.", route('dubletten.index', ['art' => 'sponsoren']));
    }

    public function ignorieren(Request $request)
    {
        $daten = $request->validate([
            'typ' => ['required', Rule::in(['laeufer', 'sponsor'])],
            'a' => ['required', 'integer'],
            'b' => ['required', 'integer', 'different:a'],
        ]);
        abort_unless($request->user()->can($daten['typ'] === 'laeufer' ? 'edit laeufer' : 'edit sponsoren'), 403);

        Dubletten::ignorieren($daten['typ'], (int) $daten['a'], (int) $daten['b']);

        return $this->erfolg('Als „keine Dublette“ markiert.');
    }

    /** @return array{0: Laeufer|Sponsor, 1: Laeufer|Sponsor} */
    private function paar(Request $request, string $klasse): array
    {
        $daten = $request->validate([
            'behalten' => ['required', 'integer'],
            'entfernen' => ['required', 'integer', 'different:behalten'],
        ]);

        return [$klasse::findOrFail($daten['behalten']), $klasse::findOrFail($daten['entfernen'])];
    }
}
