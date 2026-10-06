<!DOCTYPE html>
<html lang="de">
<head><meta charset="utf-8">@include('pdf._style')</head>
<body>
<table class="kopf"><tr>
    <td style="border:none"><h1>Auswertung {{ $event->name }}</h1><span class="muted">{{ $event->datum->format('d.m.Y') }} · Stand {{ now()->format('d.m.Y H:i') }}</span></td>
    <td style="border:none; text-align:right"><img src="{{ public_path(config('spendenlauf.logo')) }}" alt=""></td>
</tr></table>

<table class="kpi"><tr>
    <td>Spenden<br><span class="wert">{{ euro($u['brutto']) }}</span></td>
    <td>Läufer / Teilnehmer<br><span class="wert">{{ $u['anzahl'] }} / {{ $u['teilnehmer'] }}</span></td>
    <td>Runden<br><span class="wert">{{ zahl($u['runden']) }}</span></td>
    <td>Strecke<br><span class="wert">{{ zahl($u['km'], 1) }} km</span></td>
    <td>Sponsoren<br><span class="wert">{{ $u['sponsoren'] }}</span></td>
</tr></table>

<h2>Verteilung</h2>
<table>
    @foreach ($projekte as $p)<tr><td>{{ $p['label'] }}</td><td class="num">{{ euro($p['betrag']) }}</td></tr>@endforeach
    <tfoot><tr><td>Summe</td><td class="num">{{ euro($u['brutto']) }}</td></tr></tfoot>
</table>

@foreach (['Altersgruppen' => $altersgruppen, 'Kategorien' => $kategorien] as $titel => $gruppen)
    <h2>{{ $titel }}</h2>
    <table>
        <thead><tr><th></th><th class="num">Läufer</th><th class="num">Teiln.</th><th class="num">Runden</th><th class="num">Ø Runden</th><th class="num">km</th><th class="num">Spenden</th></tr></thead>
        <tbody>
        @foreach ($gruppen as $g)
            <tr><td>{{ $g['label'] }}</td><td class="num">{{ $g['anzahl'] }}</td><td class="num">{{ $g['teilnehmer'] }}</td><td class="num">{{ $g['runden'] }}</td><td class="num">{{ zahl($g['runden_schnitt'], 1) }}</td><td class="num">{{ zahl($g['km'], 1) }}</td><td class="num">{{ euro($g['spende']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endforeach

<h2>Top 10 Teams</h2>
<table>
    <thead><tr><th>Team</th><th class="num">Mitglieder</th><th class="num">Runden</th><th class="num">Spenden gesamt</th></tr></thead>
    <tbody>@foreach ($teams as $t)<tr><td>{{ $t['name'] }}</td><td class="num">{{ $t['mitglieder'] }}</td><td class="num">{{ $t['runden'] }}</td><td class="num">{{ euro($t['gesamt']) }}</td></tr>@endforeach</tbody>
</table>

<h2>Die Besten je Altersgruppe (Runden)</h2>
<table>
    @foreach ($top as $gruppe)
        <tr><td style="width:30%"><strong>{{ $gruppe['label'] }}</strong></td>
            <td>@foreach ($gruppe['zeilen'] as $i => $z){{ $i + 1 }}. {{ $z['name'] }} ({{ $z['runden'] }})@if (! $loop->last) · @endif @endforeach</td></tr>
    @endforeach
</table>
</body>
</html>
