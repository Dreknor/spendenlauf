<?php

namespace App\Enums;

enum ZahlungsArt: string
{
    case Ueberweisung = 'ueberweisung';
    case Bar = 'bar';
    case Paypal = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::Ueberweisung => 'Überweisung',
            self::Bar => 'Bar',
            self::Paypal => 'PayPal',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $a) => [$a->value => $a->label()])->all();
    }
}
