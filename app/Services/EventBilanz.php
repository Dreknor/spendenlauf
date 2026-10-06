<?php

namespace App\Services;

/**
 * Ergebnis der Berechnung einer Veranstaltung. Alle Beträge in Cent.
 */
class EventBilanz
{
    public int $bruttoCent = 0;

    public int $abgabeCent = 0;

    public int $klassenkasseCent = 0;

    public int $projekteCent = 0;

    /** Anteil aus Festbeträgen bzw. aus Rundenbeträgen am Brutto */
    public int $festCent = 0;

    public int $rundenCent = 0;

    public int $bezahltCent = 0;

    /** @var array<int, array{spende: int, einzel: int, teamanteil: int, sponsorings: int}> */
    public array $laeufer = [];

    /** @var array<int, array{spende: int, sponsorings: int}> */
    public array $teams = [];

    /** @var array<int, array{brutto: int, kasse: int}> */
    public array $klassen = [];

    /** @var array<int, array<int, int>> Klasse-ID => Sponsor-ID => Schüleranteil (Brutto) */
    public array $klassenSponsoren = [];

    /** @var array<int, int> Projekt-ID (0 = ohne Projekt) => Cent */
    public array $projekte = [];

    /** @var array<int, array{soll: int, sponsorings: int}> */
    public array $sponsoren = [];

    /** @var array<int, int> Zahlungseingang je Sponsor */
    public array $zahlungen = [];

    /** @var array<int, int> Sponsoring-ID => Brutto */
    public array $sponsorings = [];

    public function offenCent(): int
    {
        $offen = 0;
        foreach ($this->sponsoren as $id => $s) {
            $offen += max(0, $s['soll'] - ($this->zahlungen[$id] ?? 0));
        }

        return $offen;
    }

    public function laeuferSpende(int $laeuferId): int
    {
        return $this->laeufer[$laeuferId]['spende'] ?? 0;
    }

    public function sponsorSoll(int $sponsorId): int
    {
        return $this->sponsoren[$sponsorId]['soll'] ?? 0;
    }

    public function sponsorIst(int $sponsorId): int
    {
        return $this->zahlungen[$sponsorId] ?? 0;
    }
}
