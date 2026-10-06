<x-layouts.app title="Verteilung der Spenden">
    <x-page-header title="Verteilung der Spenden" :subtitle="'Soziale Abgabe '.prozent($event->soziale_abgabe_prozent).' % und Klassenkasse '.prozent($event->klassenkasse_prozent).' % werden vom Bruttobetrag berechnet, der Rest wird auf die gewählten Projekte verteilt.'">
        <a href="{{ route('auswertung.export', 'projekte') }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card>
            <x-chart :config="['type' => 'doughnut', 'euro' => true, 'labels' => $projekte->pluck('label'), 'datasets' => [['label' => 'Betrag', 'data' => $projekte->pluck('betrag')]]]" height="h-80" />
        </x-card>
        <div class="table-wrap self-start">
            <table class="table">
                <thead><tr><th>Empfänger</th><th class="num">Betrag</th><th class="num">Anteil</th></tr></thead>
                <tbody>
                @foreach ($projekte as $p)
                    <tr>
                        <td>@if ($p['art'] !== 'projekt')<x-badge :color="$p['art'] === 'abgabe' ? 'esz' : 'blue'">{{ $p['art'] === 'abgabe' ? 'Abgabe' : 'Klassen' }}</x-badge>@endif {{ $p['label'] }}</td>
                        <td class="num font-semibold">{{ euro($p['betrag']) }}</td>
                        <td class="num text-slate-500">{{ $u['brutto'] ? zahl($p['betrag'] / $u['brutto'] * 100, 1) : 0 }} %</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><td>Summe</td><td class="num">{{ euro($u['brutto']) }}</td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</x-layouts.app>
