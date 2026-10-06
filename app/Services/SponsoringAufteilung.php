<?php

namespace App\Services;

/**
 * Aufteilung eines einzelnen Sponsorings. Alle Beträge in Cent.
 */
class SponsoringAufteilung
{
    /**
     * @param  array<int, int>  $laeufer  Läufer-ID => Anteil am Brutto
     * @param  array<int, array{brutto: int, kasse: int}>  $klassen
     * @param  array<int, int>  $projekte  Projekt-ID => Betrag (0 = ohne Projekt)
     */
    public function __construct(
        public readonly int $brutto,
        public readonly int $fest,
        public readonly int $abgabe,
        public readonly array $laeufer,
        public readonly array $klassen,
        public readonly array $projekte,
    ) {}

    public function klassenkasse(): int
    {
        return array_sum(array_column($this->klassen, 'kasse'));
    }
}
