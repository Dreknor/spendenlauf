<x-layouts.app title="Auswertung Läufer">
    <x-page-header title="Läufer" :subtitle="$event->name">
        <a href="{{ request()->fullUrlWithQuery(['ansicht' => request('ansicht') === 'top' ? null : 'top']) }}" class="btn-light">
            <x-icon name="trophy" class="size-4" /> {{ request('ansicht') === 'top' ? 'Rangliste' : 'Beste je Altersgruppe' }}
        </a>
        <a href="{{ route('auswertung.export', ['laeufer'] + request()->query()) }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')
    @include('auswertung._filter')

    @if ($top)
        <div class="mb-4 flex flex-wrap gap-2 text-sm">
            <a class="btn-light btn-sm" href="{{ request()->fullUrlWithQuery(['nach_geschlecht' => request()->boolean('nach_geschlecht') ? null : 1]) }}">
                {{ request()->boolean('nach_geschlecht') ? 'Geschlechter zusammen' : 'nach Geschlecht trennen' }}
            </a>
            @foreach ([3, 5, 10] as $n)
                <a class="btn-light btn-sm {{ (int) request('anzahl', 3) === $n ? 'ring-esz-400!' : '' }}" href="{{ request()->fullUrlWithQuery(['anzahl' => $n]) }}">Top {{ $n }}</a>
            @endforeach
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($top as $gruppe)
                <x-card :title="$gruppe['label']" :padding="false">
                    <ol class="divide-y divide-slate-100 text-sm">
                        @foreach ($gruppe['zeilen'] as $i => $z)
                            <li class="flex items-center gap-3 px-4 py-2">
                                <span @class(['flex size-6 items-center justify-center rounded-full text-xs font-bold', 'bg-amber-400 text-white' => $i === 0, 'bg-slate-300 text-white' => $i === 1, 'bg-amber-700 text-white' => $i === 2, 'bg-slate-100' => $i > 2])>{{ $i + 1 }}</span>
                                <span class="flex-1">{{ $z['name'] }} <span class="text-xs text-slate-400">#{{ $z['startnummer'] }}{{ $z['klasse'] !== '–' ? ' · '.$z['klasse'] : '' }}</span></span>
                                <span class="font-semibold tabular-nums">{{ $z['runden'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            @endforeach
        </div>
    @else
        <p class="mb-2 text-sm text-slate-600">
            {{ $summen['anzahl'] }} Läufer · {{ zahl($summen['runden']) }} Runden · {{ zahl($summen['km'], 1) }} km · {{ euro($summen['spende']) }}
        </p>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th>#</th>
                    <x-sort-th column="startnummer" label="Nr." />
                    <x-sort-th column="nachname" label="Name" />
                    <x-sort-th column="alter" label="Alter" />
                    <th>Kategorie</th><th>Klasse</th><th>Team</th>
                    <x-sort-th column="runden" label="Runden" num />
                    <x-sort-th column="km" label="km" num />
                    <x-sort-th column="spende" label="Spenden" num />
                </tr></thead>
                <tbody>
                @foreach ($zeilen as $i => $z)
                    <tr>
                        <td class="text-slate-400 tabular-nums">{{ $i + 1 }}</td>
                        <td class="tabular-nums">{{ $z['startnummer'] }}</td>
                        <td><a href="{{ route('laeufer.edit', $z['id']) }}">{{ $z['nachname'] }}, {{ $z['vorname'] }}</a></td>
                        <td class="tabular-nums">{{ $z['alter'] ?? '–' }}</td>
                        <td class="text-slate-600">{{ $z['kategorie'] }}</td>
                        <td>{{ $z['klasse'] }}</td>
                        <td class="text-slate-600">{{ $z['team'] }}</td>
                        <td class="num">{{ $z['runden'] }}</td>
                        <td class="num">{{ zahl($z['km'], 1) }}</td>
                        <td class="num">{{ euro($z['spende']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
