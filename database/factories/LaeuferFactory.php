<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Model\Laeufer::class, function (Faker $faker) {
    return [
        'verwaltet_von' => $faker->randomFloat(0, 1, 20),
        'vorname'   => $faker->firstName,
        'nachname'  => $faker->lastName,
        'email'     => $faker->unique()->email,
        'geburtsdatum'  => $faker->dateTimeThisCentury->format('Y-m-d'),
        'geschlecht'    => $faker->randomElement([0, 1]),
        'startnummer' => $faker->unique()->randomFloat(0, 150, 6000),
        'team_id'   => $faker->randomElement([null, $faker->randomFloat(0, 1, 50)]),
        'runden'    => $faker->randomFloat(2, 0, 60),

    ];
});
