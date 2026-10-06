<?php

use App\Services\SpendenRechner;

it('berechnet Festbetrag plus Rundenbetrag', function () {
    expect(SpendenRechner::betragCent(20, '1.50', '10.00', null))->toBe(4000);
});

it('deckelt auf den Maximalbetrag', function () {
    expect(SpendenRechner::betragCent(50, 1, 0, 25))->toBe(2500);
});

it('behandelt Maximalbetrag 0 oder leer als „kein Maximum“', function () {
    expect(SpendenRechner::betragCent(10, 1, 0, 0))->toBe(1000)
        ->and(SpendenRechner::betragCent(10, 1, 0, ''))->toBe(1000)
        ->and(SpendenRechner::betragCent(10, 1, 0, null))->toBe(1000);
});

it('rechnet centgenau ohne Gleitkommafehler', function () {
    expect(SpendenRechner::betragCent(3, '0.10', '0.20', null))->toBe(50)
        ->and(SpendenRechner::cent('19.99'))->toBe(1999);
});

it('verteilt Beträge centgenau nach dem Largest-Remainder-Verfahren', function () {
    $verteilt = SpendenRechner::verteile(1000, [1 => 1, 2 => 1, 3 => 1]);

    expect(array_sum($verteilt))->toBe(1000)
        ->and($verteilt)->toBe([1 => 334, 2 => 333, 3 => 333]);
});

it('verteilt nach Gewichten', function () {
    expect(SpendenRechner::verteile(1000, ['a' => 30, 'b' => 10]))->toBe(['a' => 750, 'b' => 250]);
});

it('verteilt gleichmäßig wenn alle Gewichte 0 sind', function () {
    expect(SpendenRechner::verteile(100, [5 => 0, 6 => 0]))->toBe([5 => 50, 6 => 50]);
});

it('liefert für leere Gewichte nichts', function () {
    expect(SpendenRechner::verteile(100, []))->toBe([]);
});
