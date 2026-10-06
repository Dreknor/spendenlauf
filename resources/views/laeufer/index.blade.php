<x-layouts.app :title="auth()->user()->can('edit laeufer') ? 'Läufer' : 'Meine Läufer'">
    <x-page-header :title="auth()->user()->can('edit laeufer') ? 'Läufer' : 'Meine Läufer'" :subtitle="$laeufer->total().' Läufer'">
        @if ($event->anmeldungOffen() || auth()->user()->can('edit laeufer'))
            <a href="{{ route('laeufer.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Läufer anmelden</a>
        @endif
    </x-page-header>

    <x-search-form placeholder="Name oder Startnummer" class="mb-4">
        <x-select name="kategorie" :options="\App\Enums\Kategorie::options()" :selected="request('kategorie')" placeholder="alle Kategorien" class="w-44" />
        @if ($klassen->isNotEmpty())
            <x-select name="klasse" :options="$klassen" :selected="request('klasse')" placeholder="alle Klassen" class="w-36" />
        @endif
    </x-search-form>

    @if ($laeufer->isEmpty())
        <x-empty message="Keine Läufer gefunden." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <x-sort-th column="startnummer" label="Nr." />
                    <x-sort-th column="nachname" label="Name" />
                    <th>Kategorie</th>
                    <th>Team</th>
                    <x-sort-th column="geburtsdatum" label="Alter" />
                    <x-sort-th column="runden" label="Runden" num />
                    <th class="num">Spenden</th>
                    @can('edit laeufer')<th>Angemeldet von</th>@endcan
                </tr>
                </thead>
                <tbody>
                @foreach ($laeufer as $l)
                    <tr>
                        <td class="font-semibold tabular-nums">{{ $l->startnummer }}</td>
                        <td><a href="{{ route('laeufer.edit', $l) }}" class="font-medium">{{ $l->nachname }}, {{ $l->vorname }}</a></td>
                        <td class="text-slate-600">{{ $l->kategorie?->label() ?? '–' }}@if ($l->schulklasse) <x-badge color="blue">{{ $l->schulklasse->name }}</x-badge>@endif</td>
                        <td class="text-slate-600">{{ $l->team?->name ?? '–' }}</td>
                        <td class="tabular-nums">{{ $l->age ?? '–' }}</td>
                        <td class="num">{{ $l->runden }}</td>
                        <td class="num">{{ euro($bilanz->laeuferSpende($l->id)) }}</td>
                        @can('edit laeufer')<td class="text-xs text-slate-500">{{ $l->besitzer?->name }}</td>@endcan
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $laeufer->links() }}</div>
    @endif
</x-layouts.app>
