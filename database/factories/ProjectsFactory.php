<?php

namespace Database\Factories;

use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Projects> */
class ProjectsFactory extends Factory
{
    protected $model = Projects::class;

    public function definition(): array
    {
        return [
            'name' => 'Projekt '.fake()->unique()->word(),
            'description' => fake()->paragraph(),
        ];
    }
}
