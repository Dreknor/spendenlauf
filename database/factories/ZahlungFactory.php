<?php

namespace Database\Factories;

use App\Enums\ZahlungsArt;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\Zahlung;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Zahlung> */
class ZahlungFactory extends Factory
{
    protected $model = Zahlung::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'sponsor_id' => Sponsor::factory(),
            'betrag' => 10,
            'bezahlt_am' => now()->toDateString(),
            'art' => ZahlungsArt::Ueberweisung,
        ];
    }
}
