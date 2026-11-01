<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Vereinheitlicht Namen für den Dublettenabgleich:
 * „ Müller-Lüdenscheidt “ → „muellerluedenscheidt“, „Strauß“ → „strauss“.
 */
class Namen
{
    public static function normalisiere(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $name = Str::ascii($name);

        return preg_replace('/[^a-z0-9]/', '', $name);
    }

    /** „Lindenstraße 3“, „Lindenstrasse 3“ und „Lindenstr. 3“ → „lindenstr3“. */
    public static function strasse(?string $strasse): string
    {
        return str_replace('strasse', 'str', self::normalisiere($strasse));
    }

    /** Ähnlichkeit zweier normalisierter Namen: gleich, Abkürzung (Max/Maximilian) oder kleiner Tippfehler. */
    public static function aehnlich(string $a, string $b, int $toleranz = 2): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        if (min(strlen($a), strlen($b)) >= 3 && (str_starts_with($a, $b) || str_starts_with($b, $a))) {
            return true;
        }

        return levenshtein($a, $b) <= $toleranz;
    }
}
