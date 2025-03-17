<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Model\Sponsor::class, function (Faker $faker) {
    $Anrede = $faker->randomElement(['Herr', 'Frau', 'Firma']);

    switch ($Anrede) {
        case 'Herr':
            $vorname = $faker->firstName('male');
            $firmenname = null;

            break;
        case 'Frau':
            $vorname = $faker->firstName('female');
            $firmenname = null;
            break;
        case 'Firma':
            $vorname = $faker->firstName();
            $firmenname = $faker->company;
            break;
    }

    $Sponsor = [
        'anrede' => $Anrede,
        'vorname'   => $vorname,
        'nachname'  => $faker->lastName,
        'firmenname'    => $firmenname,
        'email' => $faker->unique()->safeEmail,
        'strasse'   => $faker->streetAddress,
        'plz'   => $faker->postcode,
        'ort'   => $faker->city,
        'telefon'   => $faker->phoneNumber,
    ];

    return $Sponsor;
});
