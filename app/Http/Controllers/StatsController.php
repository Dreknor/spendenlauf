<?php

namespace App\Http\Controllers;

use App\Services\Statistik;
use App\Support\CurrentEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Öffentliche Live-Statistik (auch zum Einbetten per iframe). Zeigt nur abgekürzte Namen.
 */
class StatsController extends Controller
{
    public function __invoke(?string $full = null)
    {
        $event = app(CurrentEvent::class)->get();
        abort_if($event === null, 404);

        $daten = Cache::remember("stats.public.{$event->id}", now()->addMinute(), function () use ($event) {
            $s = Statistik::fuer($event);

            // Nur einfache Arrays cachen (der Cache deserialisiert keine beliebigen Objekte)
            return [
                'u' => $s->uebersicht(),
                'top' => $s->topJeAltersgruppe(3)->map(fn ($g) => ['label' => $g['label'], 'zeilen' => $g['zeilen']->all()])->all(),
                'teams' => $s->teams()->sortByDesc('runden')->take(10)->values()->all(),
                'klassen' => $s->klassen()->where('teilnehmer', '>', 0)->sortByDesc('runden_schnitt')->values()->map(fn ($k) => collect($k)->except('zeilen')->all())->all(),
                'aktualisiert' => now()->toIso8601String(),
            ];
        });

        $daten = [
            'u' => $daten['u'],
            'top' => collect($daten['top']),
            'teams' => collect($daten['teams']),
            'klassen' => collect($daten['klassen']),
            'aktualisiert' => Carbon::parse($daten['aktualisiert']),
        ];

        $antwort = response()->view('public.stats', $daten + ['event' => $event, 'eingebettet' => $full === 'embed']);

        foreach (['Content-Security-Policy' => 'content_security_policy', 'X-Frame-Options' => 'x_frame_options'] as $header => $key) {
            if (filled($wert = config("spendenlauf.stats.$key"))) {
                $antwort->header($header, $wert);
            }
        }

        return $antwort;
    }
}
