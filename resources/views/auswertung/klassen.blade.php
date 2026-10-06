<x-layouts.app title="Klassenauswertung">
    <x-page-header title="Klassenauswertung" :subtitle="$event->hatKlassenkasse() ? prozent($event->klassenkasse_prozent).' % der Spenden von Schülerinnen und Schülern gehen in die Klassenkasse (Team-Spenden anteilig nach Runden).' : 'Für diesen Lauf ist keine Klassenkasse eingestellt.'">
        <a href="{{ route('auswertung.pdf', 'klassen') }}" target="_blank" class="btn-light"><x-icon name="printer" class="size-4" /> PDF für Klassenleitungen</a>
        <a href="{{ route('auswertung.export', 'klassen') }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')

    @if ($klassen->isEmpty())
        <x-empty message="Für diesen Lauf sind noch keine Klassen angelegt.">
            @can('edit event')<a href="{{ route('klassen.index', $event) }}" class="btn-primary">Klassen anlegen</a>@endcan
        </x-empty>
    @else
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat label="Klassenkassen gesamt" :value="euro($klassen->sum('kasse'))" accent icon="academic" />
            <x-stat label="davon bezahlt" :value="euro($klassen->sum('kasse_bezahlt'))" icon="banknotes" />
            <x-stat label="Spenden der Schüler" :value="euro($klassen->sum('brutto'))" icon="heart" />
            <x-stat label="Schüler gelaufen" :value="$klassen->sum('teilnehmer').' / '.$klassen->sum('schueler')" icon="runner" />
        </div>

        <x-card title="Klassenkasse je Klasse" class="my-6">
            <x-chart :config="['type' => 'bar', 'euro' => true, 'labels' => $klassen->pluck('name'), 'datasets' => [['label' => 'Klassenkasse', 'data' => $klassen->pluck('kasse')], ['label' => 'davon bezahlt', 'data' => $klassen->pluck('kasse_bezahlt'), 'color' => '#002561']]]" />
        </x-card>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Klasse</th><th>Klassenleitung</th><th class="num">Schüler</th><th class="num">gelaufen</th><th class="num">Runden</th><th class="num">Ø Runden</th><th class="num">km</th><th class="num">Spenden</th><th class="num">Klassenkasse</th><th class="num">bezahlt</th></tr></thead>
                <tbody>
                @foreach ($klassen as $k)
                    <tr>
                        <td><a href="{{ route('auswertung.klasse', $k['id']) }}" class="font-medium">{{ $k['name'] }}</a></td>
                        <td class="text-slate-600">{{ $k['klassenleitung'] ?? '–' }}</td>
                        <td class="num">{{ $k['schueler'] }}</td>
                        <td class="num">{{ $k['teilnehmer'] }}</td>
                        <td class="num">{{ zahl($k['runden']) }}</td>
                        <td class="num">{{ zahl($k['runden_schnitt'], 1) }}</td>
                        <td class="num">{{ zahl($k['km'], 1) }}</td>
                        <td class="num">{{ euro($k['brutto']) }}</td>
                        <td class="num font-semibold text-esz-700">{{ euro($k['kasse']) }}</td>
                        <td class="num"><x-badge :color="$k['bezahlt_quote'] >= 100 ? 'green' : ($k['bezahlt_quote'] > 0 ? 'amber' : 'gray')">{{ $k['bezahlt_quote'] }} %</x-badge></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr>
                    <td colspan="2">Summe</td>
                    <td class="num">{{ $klassen->sum('schueler') }}</td><td class="num">{{ $klassen->sum('teilnehmer') }}</td>
                    <td class="num">{{ zahl($klassen->sum('runden')) }}</td><td></td><td class="num">{{ zahl($klassen->sum('km'), 1) }}</td>
                    <td class="num">{{ euro($klassen->sum('brutto')) }}</td><td class="num">{{ euro($klassen->sum('kasse')) }}</td><td></td>
                </tr></tfoot>
            </table>
        </div>
        <p class="hint mt-2">„bezahlt“: Anteil der Klassenkasse, für den die Sponsoren bereits gezahlt haben.</p>

        @if ($ohneKlasse->isNotEmpty())
            <x-card title="Schüler ohne Klasse ({{ $ohneKlasse->count() }})" subtitle="Diese Läufer zählen für keine Klassenkasse." class="mt-6">
                <div class="flex flex-wrap gap-2 text-sm">
                    @foreach ($ohneKlasse as $z)
                        <a href="{{ route('laeufer.edit', $z['id']) }}" class="rounded bg-amber-50 px-2 py-1 text-amber-800 ring-1 ring-amber-200">{{ $z['name'] }}</a>
                    @endforeach
                </div>
            </x-card>
        @endif
    @endif
</x-layouts.app>
