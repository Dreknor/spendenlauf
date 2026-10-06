<?php

namespace Database\Factories;

use App\Enums\Kategorie;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Laeufer> */
class LaeuferFactory extends Factory
{
    protected $model = Laeufer::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'verwaltet_von' => User::factory(),
            'vorname' => fake()->firstName(),
            'nachname' => fake()->lastName(),
            'geburtsdatum' => fake()->dateTimeBetween('-60 years', '-6 years')->format('Y-m-d'),
            'geschlecht' => fake()->randomElement(['weiblich', 'maennlich']),
            'kategorie' => Kategorie::Eltern,
            'startnummer' => fake()->unique()->numberBetween(1, 99999),
            'runden' => 0,
        ];
    }

    public function schueler(?Schulklasse $klasse = null): static
    {
        return $this->state(fn (array $a) => [
            'kategorie' => Kategorie::Schueler,
            'schulklasse_id' => $klasse?->id ?? Schulklasse::factory()->state(['event_id' => $a['event_id']]),
        ]);
    }

    public function runden(int $runden): static
    {
        return $this->state(['runden' => $runden]);
    }
}
