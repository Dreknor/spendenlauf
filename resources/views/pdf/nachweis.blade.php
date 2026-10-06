@php $event = $a->event; $s = $a->sponsor; @endphp
<!DOCTYPE html>
<html lang="de">
<head><meta charset="utf-8">@include('pdf._style')</head>
<body>
<table class="kopf"><tr>
    <td style="border:none"><img src="{{ public_path(config('spendenlauf.logo')) }}" alt=""></td>
    <td style="border:none; text-align:right" class="muted">{{ $event->verein_name ?? $event->kontoinhaber }}<br>{{ $event->verein_anschrift }}</td>
</tr></table>

<p class="muted" style="font-size:8pt">{{ $event->verein_name ?? $event->kontoinhaber }} · {{ $event->verein_anschrift }}</p>
<p>{!! implode('<br>', array_map('e', $s->anschrift_zeilen)) !!}</p>

<p style="text-align:right">{{ now()->format('d.m.Y') }}</p>

<h1>Vereinfachter Spendennachweis</h1>
<p class="muted">für Zuwendungen bis 300 € (§ 50 Abs. 4 Satz 1 Nr. 2 EStDV) – {{ $event->name }}</p>

<p>Wir bestätigen den Eingang folgender Geldzuwendung:</p>
<table>
    <thead><tr><th>Datum</th><th>Zahlungsart</th><th class="num">Betrag</th></tr></thead>
    <tbody>
    @foreach ($a->zahlungen as $z)
        <tr><td>{{ $z->bezahlt_am->format('d.m.Y') }}</td><td>{{ $z->art->label() }}</td><td class="num">{{ euro($z->betrag, false) }}</td></tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="2">Gesamt</td><td class="num">{{ euro($a->konto->istCent) }}</td></tr></tfoot>
</table>

<p style="margin-top:14pt">
    Es handelt sich nicht um den Verzicht auf Erstattung von Aufwendungen.
    @if ($event->verein_zweck)
        Die Zuwendung wird ausschließlich für steuerbegünstigte Zwecke ({{ $event->verein_zweck }}) verwendet.
    @endif
</p>
@if ($event->verein_finanzamt && $event->verein_freistellung_datum)
    <p>
        Der Verein ist wegen Förderung steuerbegünstigter Zwecke nach dem letzten uns zugegangenen Freistellungsbescheid
        bzw. nach der Anlage zum Körperschaftsteuerbescheid des Finanzamtes {{ $event->verein_finanzamt }},
        StNr. {{ $event->verein_steuernummer }}, vom {{ $event->verein_freistellung_datum->format('d.m.Y') }}
        von der Körperschaftsteuer und Gewerbesteuer befreit.
    </p>
@endif
<p class="muted" style="margin-top:18pt; font-size:8.5pt">
    Dieser Nachweis gilt zusammen mit Ihrem Kontoauszug bzw. der Buchungsbestätigung als Zuwendungsnachweis.
    Er wurde maschinell erstellt und ist ohne Unterschrift gültig.
</p>
</body>
</html>
