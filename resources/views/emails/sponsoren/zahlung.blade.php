@php
    $event = $abrechnung->event;
    $sponsor = $abrechnung->sponsor;
    $konto = $abrechnung->konto;
@endphp
<x-mail::message>
# {{ $erinnerung ? 'Erinnerung: ' : '' }}{{ $event->name }}

{{ $sponsor->anrede_brief }}

@if ($erinnerung)
vielen Dank noch einmal für Ihre Unterstützung beim {{ $event->name }} am {{ $event->datum->format('d.m.Y') }}! Nach unseren Unterlagen ist Ihre Spende über **{{ euro($konto->offenCent()) }}** noch nicht (vollständig) bei uns eingegangen. Falls Sie bereits überwiesen haben, betrachten Sie diese Nachricht bitte als gegenstandslos.
@else
vielen Dank, dass Sie unseren Spendenlauf am {{ $event->datum->format('d.m.Y') }} unterstützt haben! {{ zahl($anzahlLaeufer) }} Läuferinnen und Läufer haben insgesamt **{{ euro($gesamtSumme) }}** erlaufen. Wir sind überwältigt, dass sich so viele Menschen an dieser Aktion beteiligt haben.
@endif

<x-mail::table>
| Spende für | Runden | je Runde | Festbetrag | max. | Betrag |
|:-----------|-------:|---------:|-----------:|-----:|-------:|
@foreach ($abrechnung->positionen as $p)
| {{ $p['ziel'] }} | {{ $p['runden'] }} | {{ $p['rundenBetrag'] ? euro($p['rundenBetrag']) : '–' }} | {{ $p['festBetrag'] ? euro($p['festBetrag']) : '–' }} | {{ $p['maxBetrag'] ? euro($p['maxBetrag']) : '–' }} | {{ euro($p['betrag']) }} |
@endforeach
| **Summe** | | | | | **{{ euro($konto->sollCent) }}** |
@if ($konto->istCent > 0)
| bereits bezahlt | | | | | {{ euro($konto->istCent) }} |
| **noch offen** | | | | | **{{ euro($konto->offenCent()) }}** |
@endif
</x-mail::table>

@if ($event->hatSozialeAbgabe())
{{ prozent($event->soziale_abgabe_prozent) }} % der Spenden gehen an {{ $event->soziale_abgabe_empfaenger }}.
@endif
@if ($event->hatKlassenkasse())
{{ prozent($event->klassenkasse_prozent) }} % der Spenden für Schülerinnen und Schüler fließen in die jeweilige Klassenkasse.
@endif

@if ($konto->offenCent() > 0)
## So können Sie bezahlen

@if ($event->iban)
Bitte überweisen Sie **{{ euro($konto->offenCent()) }}** bis zum {{ now()->addDays($event->zahlungsziel_tage)->format('d.m.Y') }} auf folgendes Konto:

**{{ $event->kontoinhaber }}**<br>
IBAN: {{ $event->ibanFormatiert() }}<br>
@if ($event->bic)BIC: {{ $event->bic }}@if ($event->bank) ({{ $event->bank }})@endif<br>@endif
**Verwendungszweck: {{ $abrechnung->verwendungszweck() }}**
@endif

@if ($link = $abrechnung->paypalLink())
<x-mail::button :url="$link" color="primary">
{{ euro($konto->offenCent()) }} mit PayPal bezahlen
</x-mail::button>
@endif
@endif

Ihre Aufstellung und den aktuellen Zahlungsstatus finden Sie jederzeit auf Ihrer persönlichen Seite:
<x-mail::button :url="$abrechnung->portalUrl()" color="success">
Meine Spende ansehen
</x-mail::button>

@if ($konto->sollCent > config('spendenlauf.nachweis_grenze_cent'))
Für Spenden über 300 € erhalten Sie nach Zahlungseingang auf Wunsch eine Zuwendungsbestätigung – bitte teilen Sie uns dies kurz mit.
@else
Bei Spenden bis 300 € genügt dem Finanzamt Ihr Kontoauszug zusammen mit unserem vereinfachten Spendennachweis, den Sie nach Zahlungseingang auf Ihrer persönlichen Seite herunterladen können.
@endif

@if ($event->info_url)
Fotos und aktuelle Informationen finden Sie unter [{{ preg_replace('~^https?://~', '', $event->info_url) }}]({{ $event->info_url }}).
@endif

Herzliche Grüße<br>
{{ $event->absenderName() }}
</x-mail::message>
