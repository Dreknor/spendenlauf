<?php

use App\Models\Sponsor;
use App\Models\User;

it('verlangt beim Anlegen eines Sponsors die Anschrift', function () {
    aktivesEvent();

    $this->actingAs(User::factory()->create())->post(route('sponsoren.store'), [
        'anrede' => 'Frau', 'vorname' => 'Eva', 'nachname' => 'Muster',
    ])->assertSessionHasErrors(['strasse', 'plz', 'ort']);

    expect(Sponsor::count())->toBe(0);
});
