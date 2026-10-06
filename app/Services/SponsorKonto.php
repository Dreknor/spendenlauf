<?php

namespace App\Services;

use App\Enums\ZahlungsStatus;

class SponsorKonto
{
    public function __construct(
        public readonly int $sollCent,
        public readonly int $istCent,
    ) {}

    public function offenCent(): int
    {
        return max(0, $this->sollCent - $this->istCent);
    }

    public function status(): ZahlungsStatus
    {
        return match (true) {
            $this->sollCent === 0 && $this->istCent === 0 => ZahlungsStatus::Keine,
            $this->istCent === 0 => ZahlungsStatus::Offen,
            $this->istCent < $this->sollCent => ZahlungsStatus::Teilweise,
            $this->istCent === $this->sollCent => ZahlungsStatus::Bezahlt,
            default => ZahlungsStatus::Ueberzahlt,
        };
    }

    public function istBezahlt(): bool
    {
        return $this->sollCent > 0 && $this->istCent >= $this->sollCent;
    }
}
