<x-layouts.app title="Jahresvergleich">
    <x-page-header title="Jahresvergleich" subtitle="Alle Spendenläufe im Überblick">
        <a href="{{ route('auswertung.export', 'jahresvergleich') }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')
    @php $labels = $events->map(fn ($e) => $e['datum']->format('Y')); @endphp
    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Spenden">
            <x-chart :config="['type' => 'bar', 'euro' => true, 'labels' => $labels, 'datasets' => [['label' => 'Spenden', 'data' => $events->pluck('brutto')], ['label' => 'bezahlt', 'data' => $events->pluck('bezahlt'), 'color' => '#002561']]]" />
        </x-card>
        <x-card title="Läufer und Runden">
            <x-chart :config="['type' => 'bar', 'labels' => $labels, 'datasets' => [['label' => 'Läufer', 'data' => $events->pluck('anzahl')], ['label' => 'Ø Runden', 'data' => $events->pluck('runden_schnitt'), 'color' => '#002561']]]" />
        </x-card>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Lauf</th><th>Datum</th><th class="num">Läufer</th><th class="num">gelaufen</th><th class="num">Runden</th><th class="num">km</th><th class="num">Sponsoren</th><th class="num">Spenden</th><th class="num">bezahlt</th></tr></thead>
            <tbody>
            @foreach ($events as $e)
                <tr>
                    <td class="font-medium">{{ $e['name'] }}</td>
                    <td>{{ $e['datum']->format('d.m.Y') }}</td>
                    <td class="num">{{ $e['anzahl'] }}</td>
                    <td class="num">{{ $e['teilnehmer'] }}</td>
                    <td class="num">{{ zahl($e['runden']) }}</td>
                    <td class="num">{{ zahl($e['km'], 1) }}</td>
                    <td class="num">{{ $e['sponsoren'] }}</td>
                    <td class="num font-semibold">{{ euro($e['brutto']) }}</td>
                    <td class="num">{{ euro($e['bezahlt']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
