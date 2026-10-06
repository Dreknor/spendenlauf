<?php

use App\Support\Namen;

it('normalisiert Umlaute, ß, Leerzeichen und Bindestriche', function () {
    expect(Namen::normalisiere(' Müller-Lüdenscheidt '))->toBe('muellerluedenscheidt')
        ->and(Namen::normalisiere('Strauß'))->toBe('strauss')
        ->and(Namen::normalisiere('Mueller'))->toBe(Namen::normalisiere('Müller'))
        ->and(Namen::normalisiere('José'))->toBe('jose');
});

it('erkennt ähnliche Namen', function () {
    expect(Namen::aehnlich('max', 'maximilian'))->toBeTrue()
        ->and(Namen::aehnlich('schmidt', 'schmitt'))->toBeTrue()
        ->and(Namen::aehnlich('anna', 'paul'))->toBeFalse()
        ->and(Namen::aehnlich('', ''))->toBeFalse();
});
