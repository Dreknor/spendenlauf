<x-layouts.app title="Auswertung nach Gruppen">
    <x-page-header title="Auswertung nach {{ \App\Services\Statistik::DIMENSIONEN[$dim] }}" :subtitle="$event->name">
        <a href="{{ route('auswertung.export', ['gruppen'] + request()->query() + ['dim' => $dim]) }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (\App\Services\Statistik::DIMENSIONEN as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['dim' => $key]) }}" @class(['btn-sm', 'btn-secondary' => $dim === $key, 'btn-light' => $dim !== $key])>{{ $label }}</a>
        @endforeach
    </div>
    @include('auswertung._filter')

    @if ($gruppen->isEmpty())
        <x-empty message="Keine Daten." />
    @else
        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <x-card title="Spenden">
                <x-chart :config="['type' => $gruppen->count() > 6 ? 'bar' : 'doughnut', 'euro' => true, 'labels' => $gruppen->pluck('label'), 'datasets' => [['label' => 'Spenden', 'data' => $gruppen->pluck('spende')]]]" />
            </x-card>
            <x-card title="Runden">
                <x-chart :config="['type' => 'bar', 'labels' => $gruppen->pluck('label'), 'datasets' => [['label' => 'Runden', 'data' => $gruppen->pluck('runden'), 'color' => '#002561'], ['label' => 'Teilnehmer', 'data' => $gruppen->pluck('teilnehmer'), 'color' => '#e2668a']]]" />
            </x-card>
        </div>
        @include('auswertung._kennzahlen', ['gruppen' => $gruppen, 'spalte' => \App\Services\Statistik::DIMENSIONEN[$dim]])
    @endif
</x-layouts.app>
