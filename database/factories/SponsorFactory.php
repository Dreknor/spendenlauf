<?php

namespace Database\Factories;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sponsor> */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'anrede' => fake()->randomElement(['Herr', 'Frau']),
            'vorname' => fake()->firstName(),
            'nachname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'strasse' => fake()->streetAddress(),
            'plz' => fake()->postcode(),
            'ort' => fake()->city(),
        ];
    }
}
