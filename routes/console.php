<?php

use Illuminate\Support\Facades\Schedule;

// Runden während des Laufs regelmäßig von der Zeitmessung holen (nur am Lauftag der aktiven Veranstaltung)
Schedule::command('spendenlauf:import-runden')->everyFiveMinutes()->between('7:00', '20:00')->withoutOverlapping();

// Mails aus der Warteschlange versenden (QUEUE_CONNECTION=database) – ohne dauerhaft laufenden Queue-Worker.
// Im Hintergrund, damit der Runden-Import nicht wartet; die Sperre verfällt nach 5 Minuten, falls ein Lauf abstürzt.
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()->withoutOverlapping(5)->runInBackground()
    ->when(fn () => config('queue.default') !== 'sync');

// Unbestätigte Spendenzusagen und verfallene Einladungen zur Mitverwaltung löschen
Schedule::command('model:prune')->daily();

// Täglich nach einer neuen Version suchen (Hinweis im Menü „System-Update“); installiert wird nur auf Klick
Schedule::command('spendenlauf:update --check')->dailyAt('04:15')->when(fn () => config('spendenlauf.updater.enabled'));
