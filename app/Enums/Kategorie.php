<?php

namespace App\Enums;

enum Kategorie: string
{
    case Schueler = 'schueler';
    case Eltern = 'eltern';
    case Lehrer = 'lehrer';
    case Freunde = 'freunde';
    case Extern = 'extern';

    public function label(): string
    {
        return match ($this) {
            self::Schueler => 'Schüler/in',
            self::Eltern => 'Eltern',
            self::Lehrer => 'Lehrer/in & Mitarbeitende',
            self::Freunde => 'Freunde & Verwandte',
            self::Extern => 'Extern',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $k) => [$k->value => $k->label()])->all();
    }
}
