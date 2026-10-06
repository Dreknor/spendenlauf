<?php

use App\Models\Event;
use App\Support\CurrentEvent;

if (! function_exists('euro')) {
    /** Formatiert einen Betrag in Cent (int) oder Euro (float/string) als „1.234,56 €“. */
    function euro(int|float|string|null $betrag, bool $istCent = true): string
    {
        $euro = $istCent && is_int($betrag) ? $betrag / 100 : (float) $betrag;

        return number_format($euro, 2, ',', '.').' €';
    }
}

if (! function_exists('zahl')) {
    function zahl(int|float|null $wert, int $dezimalen = 0): string
    {
        return number_format((float) $wert, $dezimalen, ',', '.');
    }
}

if (! function_exists('aktuelles_event')) {
    function aktuelles_event(): ?Event
    {
        return app(CurrentEvent::class)->get();
    }
}

if (! function_exists('prozent')) {
    /** „10“, „7,5“ – ohne überflüssige Nachkommastellen. */
    function prozent(int|float|string|null $wert): string
    {
        return rtrim(rtrim(number_format((float) $wert, 2, ',', '.'), '0'), ',');
    }
}
