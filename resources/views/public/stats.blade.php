<x-layouts.public title="Live-Statistik" :bare="$eingebettet">
    @push('head')
        @if ($event->istLauftag())<meta http-equiv="refresh" content="{{ config('spendenlauf.stats.reload_sekunden') }}">@endif
    @endpush
    @unless ($eingebettet)
        <div class="mb-6 flex flex-wrap items-end justify-between gap-2">
            <h1 class="text-3xl">Live-Statistik</h1>
            <p class="text-sm text-slate-500">Stand {{ $aktualisiert->format('H:i') }} Uhr{{ $event->istLauftag() ? ' · aktualisiert sich automatisch' : '' }}</p>
        </div>
    @endunless

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Erlaufen" :value="euro($u['brutto'])" accent icon="heart" />
        <x-stat label="Läufer unterwegs" :value="zahl($u['teilnehmer'])" icon="runner" :hint="zahl($u['anzahl']).' angemeldet'" />
        <x-stat label="Runden" :value="zahl($u['runden'])" icon="clock" />
        <x-stat label="Strecke" :value="zahl($u['km'], 1).' km'" icon="trophy" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <h2 class="text-lg">Die meisten Runden je Altersgruppe</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($top as $gruppe)
                    <div class="card p-4">
                        <p class="text-sm font-semibold text-esz-600">{{ $gruppe['label'] }}</p>
                        <ol class="mt-2 space-y-1 text-sm">
                            @foreach ($gruppe['zeilen'] as $i => $z)
                                <li class="flex justify-between"><span>{{ $i + 1 }}. {{ $z['kurzname'] }}</span><span class="font-semibold tabular-nums">{{ $z['runden'] }}</span></li>
                            @endforeach
                        </ol>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Noch keine Runden.</p>
                @endforelse
            </div>
        </div>
        <div class="space-y-6">
            <x-card title="Teams" :padding="false">
                <ol class="divide-y divide-slate-100 text-sm">
                    @forelse ($teams as $i => $t)
                        <li class="flex justify-between px-4 py-2"><span>{{ $i + 1 }}. {{ $t['name'] }}</span><span class="font-semibold tabular-nums">{{ $t['runden'] }}</span></li>
                    @empty
                        <li class="px-4 py-2 text-slate-500">–</li>
                    @endforelse
                </ol>
            </x-card>
            @if ($klassen->isNotEmpty())
                <x-card title="Klassen (Ø Runden)" :padding="false">
                    <ol class="divide-y divide-slate-100 text-sm">
                        @foreach ($klassen->take(10) as $i => $k)
                            <li class="flex justify-between px-4 py-2"><span>{{ $i + 1 }}. {{ $k['name'] }}</span><span class="font-semibold tabular-nums">{{ zahl($k['runden_schnitt'], 1) }}</span></li>
                        @endforeach
                    </ol>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.public>
