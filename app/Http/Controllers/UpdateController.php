<?php

namespace App\Http\Controllers;

use App\Services\Updater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class UpdateController extends Controller
{
    public function __construct(private readonly Updater $updater) {}

    public function index()
    {
        $this->aktiviert();

        return view('updates.index', [
            'version' => $this->updater->version(),
            'pruefung' => $this->updater->letztePruefung(),
            'status' => $this->updater->status(),
        ]);
    }

    public function pruefen()
    {
        $this->aktiviert();

        $pruefung = $this->updater->pruefen();

        if ($pruefung['fehler']) {
            return $this->fehler($pruefung['fehler'], route('updates.index'));
        }

        return $this->erfolg($pruefung['neu'] > 0
            ? "Es ist eine neue Version verfügbar ({$pruefung['neu']} Änderung(en))."
            : 'Die installierte Version ist aktuell.', route('updates.index'));
    }

    public function starten(Request $request)
    {
        $this->aktiviert();

        try {
            $this->updater->starten($request->user()->name, $request->boolean('erzwingen'));
        } catch (RuntimeException $e) {
            return $this->fehler($e->getMessage(), route('updates.index'));
        }

        return $this->erfolg('Das Update wurde gestartet. Der Fortschritt wird unten angezeigt.', route('updates.index'));
    }

    /** Fortschritt für die Live-Anzeige (bleibt auch im Wartungsmodus erreichbar, siehe bootstrap/app.php). */
    public function status(): JsonResponse
    {
        $this->aktiviert();

        return response()->json($this->updater->status());
    }

    private function aktiviert(): void
    {
        abort_unless($this->updater->aktiviert(), 404);
    }
}
