<?php

namespace App\Actions;

use App\Models\Event;
use App\Services\SpendenRechner;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Setzt einen Lauf zurück, der noch nicht stattgefunden hat: Läufer, Teams, Spenden, Zahlungen,
 * Startnummern und Mails dieses Laufs werden entfernt, übrig bleiben die Benutzerkonten
 * sowie Einstellungen, Projekte und Klassen des Laufs.
 *
 * Vergangene Läufe sind gesperrt – deren Daten bleiben dauerhaft erhalten. Sponsoren werden nur gelöscht,
 * wenn sie ausschließlich in diesem Lauf vorkamen; wer auch in einem anderen Lauf gespendet hat, bleibt.
 */
class LaufBereinigen
{
    public function __construct(private readonly Event $event) {}

    public static function erlaubt(Event $event): bool
    {
        return ! $event->laufVorbei();
    }

    /** @return array{laeufer: int, teams: int, sponsorings: int, zahlungen: int, startnummern: int, sponsoren: int} */
    public function zaehlen(): array
    {
        return [
            'laeufer' => $this->tabelle('laeufers')->count(),
            'teams' => $this->tabelle('teams')->count(),
            'sponsorings' => $this->tabelle('sponsorings')->count(),
            'zahlungen' => $this->tabelle('zahlungen')->count(),
            'startnummern' => $this->tabelle('startnummern')->count(),
            'sponsoren' => count($this->nurHierSponsoren()),
        ];
    }

    public function hatDaten(): bool
    {
        return collect(['laeufers', 'teams', 'sponsorings', 'zahlungen', 'startnummern'])
            ->contains(fn ($t) => $this->tabelle($t)->exists());
    }

    /** @return array{laeufer: int, teams: int, sponsorings: int, zahlungen: int, startnummern: int, sponsoren: int} */
    public function ausfuehren(): array
    {
        if (! self::erlaubt($this->event)) {
            throw new RuntimeException('Vergangene Läufe können nicht bereinigt werden – ihre Daten bleiben erhalten.');
        }

        $anzahl = DB::transaction(function () {
            $anzahl = $this->zaehlen();
            $sponsoren = $this->nurHierSponsoren();

            $laeufer = $this->tabelle('laeufers')->select('id');
            $sponsorings = $this->tabelle('sponsorings')->select('id');

            DB::table('spendenzusagen')->where('event_id', $this->event->id)->orWhereIn('laeufer_id', $laeufer)->delete();
            DB::table('sponsor_mails')->where('event_id', $this->event->id)->delete();
            DB::table('zahlungen')->where('event_id', $this->event->id)->delete();
            DB::table('projects_sponsoring')->whereIn('sponsoring_id', $sponsorings)->delete();
            DB::table('sponsorings')->where('event_id', $this->event->id)->delete();

            DB::table('mitverwaltung_anfragen')->whereIn('laeufer_id', $laeufer)->delete();
            DB::table('mitverwaltung_einladungen')->whereIn('laeufer_id', $laeufer)->delete();
            DB::table('laeufer_user')->whereIn('laeufer_id', $laeufer)->delete();
            // auch bereits gelöschte (Soft Delete) Läufer, sonst blieben deren Daten liegen
            DB::table('laeufers')->where('event_id', $this->event->id)->delete();
            DB::table('teams')->where('event_id', $this->event->id)->delete();
            DB::table('startnummern')->where('event_id', $this->event->id)->delete();

            DB::table('sponsor_user')->whereIn('sponsor_id', $sponsoren)->delete();
            DB::table('sponsors')->whereIn('id', $sponsoren)->delete();

            return $anzahl;
        });

        SpendenRechner::vergessen();

        return $anzahl;
    }

    /**
     * Sponsoren, die in diesem Lauf eine Spende, Zahlung oder Mail haben, in keinem anderen Lauf aber.
     *
     * @return list<int>
     */
    private function nurHierSponsoren(): array
    {
        $hier = $this->tabelle('sponsorings')->pluck('sponsor_id')
            ->merge($this->tabelle('zahlungen')->pluck('sponsor_id'))
            ->merge($this->tabelle('sponsor_mails')->pluck('sponsor_id'))
            ->unique();

        if ($hier->isEmpty()) {
            return [];
        }

        $woanders = collect(['sponsorings', 'zahlungen', 'sponsor_mails'])
            ->flatMap(fn ($t) => DB::table($t)->where('event_id', '!=', $this->event->id)->whereIn('sponsor_id', $hier)->pluck('sponsor_id'))
            ->unique();

        return $hier->diff($woanders)->map(fn ($id) => (int) $id)->values()->all();
    }

    private function tabelle(string $name): Builder
    {
        return DB::table($name)->where('event_id', $this->event->id);
    }
}
