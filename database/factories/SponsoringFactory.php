<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Model\Sponsoring::class, function (Faker $faker) {
    return [
        'sponsor_id'    => $faker->randomFloat(2, 1, 50),
        'verwaltet_von' => $faker->randomFloat(2, 1, 20),
        'sponsorable_type'  => $faker->randomElement([\App\Model\Laeufer::class, \App\Model\Teams::class]),
        'sponsorable_id' => $faker->randomFloat(2, 1, 20),
        'rundenBetrag'  => $faker->randomElement([0, 0, 0, $faker->randomFloat(1, 0, 25)]),
        'festBetrag'  => $faker->randomElement([0, $faker->randomFloat(1, 0, 500)]),
        'maxBetrag'     => $faker->randomElement([null, null, $faker->randomFloat(1, 50, 1500)]),
    ];
});
