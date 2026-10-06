<?php

namespace App\Console\Commands;

use App\Actions\ImportRunden;
use App\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('spendenlauf:import-runden {--force : Auch außerhalb des Lauftags importieren} {--url= : Abweichende URL}')]
#[Description('Importiert die Runden von der Ergebnisseite der Zeitmessung (Import-URL der aktiven Veranstaltung).')]
class ImportRundenCommand extends Command
{
    public function handle(): int
    {
        $event = Event::where('aktiv', true)->first();

        if (! $event) {
            $this->warn('Keine aktive Veranstaltung.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $event->istLauftag()) {
            $this->line('Heute ist kein Lauftag – kein Import.');

            return self::SUCCESS;
        }

        if (blank($this->option('url') ?? $event->import_url)) {
            $this->line('Keine Import-URL hinterlegt.');

            return self::SUCCESS;
        }

        try {
            $ergebnis = (new ImportRunden($event))->vonUrl($this->option('url'));
        } catch (Throwable $e) {
            report($e);
            $this->error('Import fehlgeschlagen: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%d Zeilen gelesen, %d aktualisiert, %d unverändert, %d manuelle Werte übersprungen, %d unbekannte Startnummern.',
            $ergebnis['zeilen'], $ergebnis['aktualisiert'], $ergebnis['unveraendert'], count($ergebnis['uebersprungen']), count($ergebnis['unbekannt']),
        ));

        return self::SUCCESS;
    }
}
