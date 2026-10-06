<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => '8. Radebeuler Spendenlauf',
            'nummer' => 8,
            'datum' => now()->addMonth()->toDateString(),
            'aktiv' => true,
            'runden_laenge_m' => 400,
            'dauer_minuten' => 120,
            'soziale_abgabe_prozent' => 10,
            'soziale_abgabe_empfaenger' => 'Sozialprojekt',
            'klassenkasse_prozent' => 10,
            'kontoinhaber' => 'Förderverein',
            'iban' => 'DE40850550000500027960',
            'verwendungszweck_prefix' => 'Spendenlauf',
            'zahlungsziel_tage' => 14,
        ];
    }

    public function vorbei(): static
    {
        return $this->state(['datum' => now()->subWeek()->toDateString()]);
    }

    public function ohneAbzuege(): static
    {
        return $this->state(['soziale_abgabe_prozent' => 0, 'klassenkasse_prozent' => 0]);
    }
}
