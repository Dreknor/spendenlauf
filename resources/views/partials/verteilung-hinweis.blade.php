@if ($event->hatSozialeAbgabe() || $event->hatKlassenkasse())
    <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
        @if ($event->hatSozialeAbgabe())
            <p>{{ prozent($event->soziale_abgabe_prozent) }} % jeder Spende gehen an <strong>{{ $event->soziale_abgabe_empfaenger }}</strong>.</p>
            @if ($event->soziale_abgabe_beschreibung)<p class="mt-1">{{ $event->soziale_abgabe_beschreibung }}</p>@endif
        @endif
        @if ($event->hatKlassenkasse())
            <p class="mt-1">{{ prozent($event->klassenkasse_prozent) }} % der Spenden für Schülerinnen und Schüler gehen in deren Klassenkasse.</p>
        @endif
    </div>
@endif
