<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Teams;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Teams> */
class TeamsFactory extends Factory
{
    protected $model = Teams::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => 'Team '.fake()->unique()->word(),
            'open' => true,
            'verwaltet_von' => User::factory(),
        ];
    }
}
