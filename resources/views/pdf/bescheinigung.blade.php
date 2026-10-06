<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <style>
        @font-face { font-family: 'MetaPro'; src: url('{{ public_path('fonts/metapro.ttf') }}') format('truetype'); }
        @page { margin: 0; }
        body { margin: 0; font-family: 'MetaPro', DejaVu Sans, sans-serif; color: #002561; }
        #bg { position: absolute; top: 0; left: 0; width: 210mm; height: 297mm; z-index: -1; }
        .name { position: absolute; top: 30%; left: 0; width: 210mm; text-align: center; }
        .name .gross { font-size: 26pt; font-weight: bold; }
        .text { position: absolute; top: 48%; left: 25mm; width: 160mm; text-align: center; font-size: 16pt; line-height: 1.5; }
    </style>
</head>
<body>
@if (file_exists(storage_path('app/urkunde_blank-scaled.png')))
    <img id="bg" src="{{ storage_path('app/urkunde_blank-scaled.png') }}" alt="">
@endif
<div class="name">
    <div class="gross">{{ $laeufer->vorname }} {{ $laeufer->nachname }}</div>
    @if ($laeufer->geburtsdatum)<div>geb. {{ $laeufer->geburtsdatum->format('d.m.Y') }}</div>@endif
</div>
<div class="text">
    hat beim {{ $event->name }} am {{ $event->datum->format('d.m.Y') }}
    in {{ $event->dauer_minuten % 60 === 0 ? ($event->dauer_minuten / 60).' '.($event->dauer_minuten === 60 ? 'Stunde' : 'Stunden') : $event->dauer_minuten.' Minuten' }}
    <strong>{{ $laeufer->runden }} Runden</strong> zu je {{ $event->runden_laenge_m }} m absolviert.
    Das entspricht einer Strecke von <strong>{{ zahl($event->km($laeufer->runden), 1) }} km</strong>.
</div>
</body>
</html>
