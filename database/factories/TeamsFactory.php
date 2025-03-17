<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Model\Teams::class, function (Faker $faker) {
    return [
        'name'  => $faker->team,
        'verwaltet_von' => $faker->randomFloat(2, 1, 20),
        'open'  => $faker->randomElement([0, 1]),
    ];
});
