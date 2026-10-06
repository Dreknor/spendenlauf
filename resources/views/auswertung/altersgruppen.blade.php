<x-layouts.app title="Auswertung Altersgruppen">
    <x-page-header title="Altersgruppen" :subtitle="$event->name.' · Alter am Lauftag'">
        <a href="{{ route('auswertung.export', 'altersgruppen') }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
        @can('edit event')<a href="{{ route('events.edit', $event) }}" class="btn-light">Gruppen anpassen</a>@endcan
    </x-page-header>
    @include('auswertung._nav')

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Läufer je Altersgruppe und Geschlecht">
            <x-chart :config="['type' => 'bar', 'stacked' => true, 'labels' => $kreuz->pluck('label'), 'datasets' => $geschlechter->map(fn ($g) => ['label' => $g, 'data' => $kreuz->map(fn ($k) => $k['geschlechter'][$g]['anzahl'] ?? 0)])->values()]" />
        </x-card>
        <x-card title="Ø Runden je Altersgruppe">
            <x-chart :config="['type' => 'bar', 'labels' => $gruppen->pluck('label'), 'datasets' => [['label' => 'Ø Runden', 'data' => $gruppen->pluck('runden_schnitt'), 'color' => '#002561']]]" />
        </x-card>
    </div>

    @include('auswertung._kennzahlen', ['gruppen' => $gruppen, 'spalte' => 'Altersgruppe'])

    <h2 class="mt-8 mb-3 text-lg">Altersgruppe × Geschlecht</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th rowspan="2">Altersgruppe</th>@foreach ($geschlechter as $g)<th colspan="3" class="text-center!">{{ $g }}</th>@endforeach</tr>
            <tr>@foreach ($geschlechter as $g)<th class="num">Läufer</th><th class="num">Ø Runden</th><th class="num">Spenden</th>@endforeach</tr>
            </thead>
            <tbody>
            @foreach ($kreuz as $zeile)
                <tr>
                    <td class="font-medium">{{ $zeile['label'] }}</td>
                    @foreach ($geschlechter as $g)
                        @php $w = $zeile['geschlechter'][$g] ?? null; @endphp
                        <td class="num">{{ $w['anzahl'] ?? 0 }}</td>
                        <td class="num">{{ $w ? zahl($w['runden_schnitt'], 1) : '–' }}</td>
                        <td class="num">{{ $w ? euro($w['spende']) : '–' }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
