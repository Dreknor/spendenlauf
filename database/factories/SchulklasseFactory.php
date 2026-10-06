<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Schulklasse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Schulklasse> */
class SchulklasseFactory extends Factory
{
    protected $model = Schulklasse::class;

    public function definition(): array
    {
        $stufe = fake()->numberBetween(1, 10);

        return [
            'event_id' => Event::factory(),
            'name' => $stufe.fake()->randomElement(['a', 'b', 'c']).fake()->unique()->numberBetween(1, 9999),
            'stufe' => $stufe,
        ];
    }
}
