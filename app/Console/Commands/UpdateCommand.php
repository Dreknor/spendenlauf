<?php

namespace App\Console\Commands;

use App\Enums\UpdateZustand;
use App\Services\Updater;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('spendenlauf:update {--check : Nur nach einer neuen Version suchen} {--force : Installationsschritte auch ohne neue Version ausführen}')]
#[Description('Spielt die neueste Version aus dem git-Repository ein (Wartungsmodus, Composer, Frontend, Migrationen, Caches).')]
class UpdateCommand extends Command
{
    public function handle(Updater $updater): int
    {
        if ($this->option('check')) {
            $pruefung = $updater->pruefen();

            if ($pruefung['fehler']) {
                $this->error($pruefung['fehler']);

                return self::FAILURE;
            }

            $this->info($pruefung['neu'] > 0
                ? "{$pruefung['neu']} neue Commit(s) in {$pruefung['ziel']} verfügbar."
                : 'Die installierte Version ist aktuell.');

            return self::SUCCESS;
        }

        try {
            $zustand = $updater->ausfuehren((bool) $this->option('force'), fn (string $text) => $this->output->write($text));
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $meldung = $updater->status()['meldung'] ?? $zustand->label();

        if ($zustand === UpdateZustand::Fehlgeschlagen) {
            $this->error($meldung);

            return self::FAILURE;
        }

        $this->info($meldung);

        return self::SUCCESS;
    }
}
