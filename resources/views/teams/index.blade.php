<x-layouts.app title="Teams">
    <x-page-header title="Teams" subtitle="Bei Team-Spenden zählen die Runden aller Teammitglieder.">
        <a href="{{ route('teams.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Team gründen</a>
    </x-page-header>
    <x-search-form placeholder="Teamname" class="mb-4" />
    @if ($teams->isEmpty())
        <x-empty message="Noch keine Teams." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Team</th><th>Verantwortlich</th><th></th><th class="num">Läufer</th><th class="num">Runden</th><th class="num">Team-Spenden</th></tr></thead>
                <tbody>
                @foreach ($teams as $t)
                    <tr>
                        <td>
                            @can('view', $t)
                                <a href="{{ route('teams.edit', $t) }}" class="font-medium">{{ $t->name }}</a>
                            @else
                                {{ $t->name }}
                            @endcan
                        </td>
                        <td class="text-slate-600">{{ $t->besitzer?->name }}</td>
                        <td>@if ($t->open)<x-badge color="green">offen</x-badge>@else<x-badge>geschlossen</x-badge>@endif</td>
                        <td class="num">{{ $t->laeufer_count }}</td>
                        <td class="num">{{ (int) $t->laeufer_sum_runden }}</td>
                        <td class="num">{{ euro($bilanz->teams[$t->id]['spende'] ?? 0) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $teams->links() }}</div>
    @endif
</x-layouts.app>
