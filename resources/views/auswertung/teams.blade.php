<x-layouts.app title="Auswertung Teams">
    <x-page-header title="Teams" :subtitle="$event->name">
        <a href="{{ route('auswertung.export', 'teams') }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')
    @if ($teams->isEmpty())
        <x-empty message="Keine Teams." />
    @else
        <x-card title="Runden der Top 15 Teams" class="mb-6">
            <x-chart :config="['type' => 'bar', 'horizontal' => true, 'labels' => $teams->take(15)->pluck('name'), 'datasets' => [['label' => 'Runden', 'data' => $teams->take(15)->pluck('runden')]]]" height="h-96" />
        </x-card>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Team</th><th class="num">Mitglieder</th><th class="num">gelaufen</th><th class="num">Runden</th><th class="num">Ø Runden</th><th class="num">km</th><th class="num">Team-Spenden</th><th class="num">Einzelspenden</th><th class="num">Gesamt</th></tr></thead>
                <tbody>
                @foreach ($teams as $i => $t)
                    <tr>
                        <td class="text-slate-400">{{ $i + 1 }}</td>
                        <td><a href="{{ route('teams.edit', $t['id']) }}">{{ $t['name'] }}</a></td>
                        <td class="num">{{ $t['mitglieder'] }}</td>
                        <td class="num">{{ $t['teilnehmer'] }}</td>
                        <td class="num font-semibold">{{ $t['runden'] }}</td>
                        <td class="num">{{ zahl($t['runden_schnitt'], 1) }}</td>
                        <td class="num">{{ zahl($t['km'], 1) }}</td>
                        <td class="num">{{ euro($t['team_spende']) }}</td>
                        <td class="num">{{ euro($t['einzel_spende']) }}</td>
                        <td class="num font-semibold">{{ euro($t['gesamt']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
