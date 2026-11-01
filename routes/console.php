<?php

use Illuminate\Support\Facades\Schedule;

// Runden während des Laufs regelmäßig von der Zeitmessung holen (nur am Lauftag der aktiven Veranstaltung)
Schedule::command('spendenlauf:import-runden')->everyFiveMinutes()->between('7:00', '20:00')->withoutOverlapping();

// Täglich nach einer neuen Version suchen (Hinweis im Menü „System-Update“); installiert wird nur auf Klick
Schedule::command('spendenlauf:update --check')->dailyAt('04:15')->when(fn () => config('spendenlauf.updater.enabled'));
