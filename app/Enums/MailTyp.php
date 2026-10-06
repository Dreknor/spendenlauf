<?php

namespace App\Enums;

enum MailTyp: string
{
    case Aufforderung = 'aufforderung';
    case Erinnerung = 'erinnerung';
    case Nachweis = 'nachweis';

    public function label(): string
    {
        return match ($this) {
            self::Aufforderung => 'Zahlungsaufforderung',
            self::Erinnerung => 'Erinnerung',
            self::Nachweis => 'Spendennachweis',
        };
    }
}
