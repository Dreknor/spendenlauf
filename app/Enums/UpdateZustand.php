<?php

namespace App\Enums;

enum UpdateZustand: string
{
    case Gestartet = 'gestartet';
    case Laeuft = 'laeuft';
    case Erfolgreich = 'erfolgreich';
    case Aktuell = 'aktuell';
    case Fehlgeschlagen = 'fehlgeschlagen';
    case Abgebrochen = 'abgebrochen';

    public function label(): string
    {
        return match ($this) {
            self::Gestartet => 'wird gestartet',
            self::Laeuft => 'läuft',
            self::Erfolgreich => 'erfolgreich',
            self::Aktuell => 'bereits aktuell',
            self::Fehlgeschlagen => 'fehlgeschlagen',
            self::Abgebrochen => 'abgebrochen',
        };
    }

    public function farbe(): string
    {
        return match ($this) {
            self::Gestartet, self::Laeuft => 'blue',
            self::Erfolgreich, self::Aktuell => 'green',
            self::Fehlgeschlagen, self::Abgebrochen => 'red',
        };
    }

    public function aktiv(): bool
    {
        return $this === self::Gestartet || $this === self::Laeuft;
    }
}
