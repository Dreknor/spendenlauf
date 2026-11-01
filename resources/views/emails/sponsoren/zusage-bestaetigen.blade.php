<x-mail::message>
# Bitte bestätigen Sie Ihre Spendenzusage

{{ $zusage->sponsorDaten()->anrede_brief }}

vielen Dank, dass Sie **{{ $laeufer->vorname }} {{ mb_substr($laeufer->nachname, 0, 1) }}.** beim {{ $event->name }} am {{ $event->datum->format('d.m.Y') }} unterstützen möchten! Ihre Zusage:

<x-mail::table>
| | |
|:--|--:|
@if ((float) ($daten['rundenBetrag'] ?? 0) > 0)
| je Runde | {{ euro($daten['rundenBetrag'], false) }} |
@endif
@if ((float) ($daten['festBetrag'] ?? 0) > 0)
| Festbetrag | {{ euro($daten['festBetrag'], false) }} |
@endif
@if ((float) ($daten['maxBetrag'] ?? 0) > 0)
| höchstens | {{ euro($daten['maxBetrag'], false) }} |
@endif
</x-mail::table>

Damit niemand in Ihrem Namen Spenden zusagen kann, zählt die Zusage erst, wenn Sie sie bestätigen:

<x-mail::button :url="$link">
Spendenzusage bestätigen
</x-mail::button>

Der Link ist {{ \App\Models\Spendenzusage::GUELTIG_TAGE }} Tage gültig. Nach dem Lauf erhalten Sie eine E-Mail mit dem erlaufenen Betrag und den Zahlungsinformationen.

Haben Sie keine Spende zugesagt, ignorieren Sie diese E-Mail bitte. Ihre Angaben werden dann nach {{ \App\Models\Spendenzusage::GUELTIG_TAGE }} Tagen automatisch gelöscht.

Viele Grüße<br>
{{ $event->absenderName() }}
</x-mail::message>
