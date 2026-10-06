<?php

namespace App\Enums;

enum ZahlungsStatus: string
{
    case Offen = 'offen';
    case Teilweise = 'teilweise';
    case Bezahlt = 'bezahlt';
    case Ueberzahlt = 'ueberzahlt';
    case Keine = 'keine';

    public function label(): string
    {
        return match ($this) {
            self::Offen => 'offen',
            self::Teilweise => 'teilweise bezahlt',
            self::Bezahlt => 'bezahlt',
            self::Ueberzahlt => 'überzahlt',
            self::Keine => 'keine Spende',
        };
    }

    public function farbe(): string
    {
        return match ($this) {
            self::Offen => 'red',
            self::Teilweise => 'amber',
            self::Bezahlt => 'green',
            self::Ueberzahlt => 'blue',
            self::Keine => 'gray',
        };
    }
}
