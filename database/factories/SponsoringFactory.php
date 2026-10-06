<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sponsoring> */
class SponsoringFactory extends Factory
{
    protected $model = Sponsoring::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'sponsor_id' => Sponsor::factory(),
            'rundenBetrag' => 1,
            'festBetrag' => 0,
            'maxBetrag' => null,
        ];
    }

    public function fuer(Laeufer|Teams $ziel): static
    {
        return $this->state([
            'event_id' => $ziel->event_id,
            'sponsorable_type' => $ziel->getMorphClass(),
            'sponsorable_id' => $ziel->id,
        ]);
    }
}
