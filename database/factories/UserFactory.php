<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'vorname' => fake()->firstName(),
            'nachname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    /** @param  list<string>  $rechte */
    public function mitRechten(array $rechte): static
    {
        return $this->afterCreating(fn (User $user) => $user->givePermissionTo($rechte));
    }
}
