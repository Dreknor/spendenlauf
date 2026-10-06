<?php

use Illuminate\Support\Facades\Schedule;

// Runden während des Laufs regelmäßig von der Zeitmessung holen (nur am Lauftag der aktiven Veranstaltung)
Schedule::command('spendenlauf:import-runden')->everyFiveMinutes()->between('7:00', '20:00')->withoutOverlapping();
