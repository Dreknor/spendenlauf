<!DOCTYPE html>
<html lang="de">
<head><meta charset="utf-8">@include('pdf._style')</head>
<body>
<div class="seite">
    <h1>Klassenauswertung {{ $event->name }}</h1>
    <p class="muted">{{ prozent($event->klassenkasse_prozent) }} % der Spenden von Schülerinnen und Schülern gehen in die Klassenkasse. Stand {{ now()->format('d.m.Y H:i') }}</p>
    <table>
        <thead><tr><th>Klasse</th><th class="num">Schüler</th><th class="num">Teiln.</th><th class="num">Runden</th><th class="num">Ø</th><th class="num">Spenden</th><th class="num">Klassenkasse</th><th class="num">bezahlt</th></tr></thead>
        <tbody>
        @foreach ($klassen as $k)
            <tr><td>{{ $k['name'] }}</td><td class="num">{{ $k['schueler'] }}</td><td class="num">{{ $k['teilnehmer'] }}</td><td class="num">{{ $k['runden'] }}</td><td class="num">{{ zahl($k['runden_schnitt'], 1) }}</td><td class="num">{{ euro($k['brutto']) }}</td><td class="num"><strong>{{ euro($k['kasse']) }}</strong></td><td class="num">{{ $k['bezahlt_quote'] }} %</td></tr>
        @endforeach
        </tbody>
        <tfoot><tr><td>Summe</td><td class="num">{{ $klassen->sum('schueler') }}</td><td class="num">{{ $klassen->sum('teilnehmer') }}</td><td class="num">{{ $klassen->sum('runden') }}</td><td></td><td class="num">{{ euro($klassen->sum('brutto')) }}</td><td class="num">{{ euro($klassen->sum('kasse')) }}</td><td></td></tr></tfoot>
    </table>
</div>
@foreach ($klassen->where('schueler', '>', 0) as $k)
    <div class="seite">
        <h1>Klasse {{ $k['name'] }}</h1>
        <p class="muted">{{ $k['klassenleitung'] ? 'Klassenleitung: '.$k['klassenleitung'].' · ' : '' }}Klassenkasse: <strong>{{ euro($k['kasse']) }}</strong> (davon bezahlt {{ euro($k['kasse_bezahlt']) }})</p>
        <table>
            <thead><tr><th>Nr.</th><th>Name</th><th class="num">Runden</th><th class="num">km</th><th class="num">Spenden</th></tr></thead>
            <tbody>
            @foreach ($k['zeilen']->sortByDesc('runden') as $z)
                <tr><td>{{ $z['startnummer'] }}</td><td>{{ $z['name'] }}</td><td class="num">{{ $z['runden'] }}</td><td class="num">{{ zahl($z['km'], 1) }}</td><td class="num">{{ euro($z['spende']) }}</td></tr>
            @endforeach
            </tbody>
            <tfoot><tr><td></td><td>Summe</td><td class="num">{{ $k['runden'] }}</td><td class="num">{{ zahl($k['km'], 1) }}</td><td class="num">{{ euro($k['brutto']) }}</td></tr></tfoot>
        </table>
    </div>
@endforeach
</body>
</html>
