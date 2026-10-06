<x-layouts.app title="Statistiken">
    <x-page-header title="Statistiken" :subtitle="$event->name.' · Stand '.now()->format('d.m.Y H:i')">
        <a href="{{ route('auswertung.pdf', 'uebersicht') }}" target="_blank" class="btn-light"><x-icon name="printer" class="size-4" /> PDF</a>
    </x-page-header>
    @include('auswertung._nav')

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Spenden gesamt" :value="euro($u['brutto'])" icon="heart" accent :hint="'davon '.euro($u['bezahlt']).' bezahlt'" />
        <x-stat label="Läufer" :value="zahl($u['anzahl'])" icon="runner" :hint="$u['teilnehmer'].' gelaufen'" />
        <x-stat label="Runden" :value="zahl($u['runden'])" icon="clock" :hint="'Ø '.zahl($u['runden_schnitt'], 1).' · max. '.$u['runden_max']" />
        <x-stat label="Strecke" :value="zahl($u['km'], 1).' km'" icon="trophy" />
        <x-stat label="Sponsoren" :value="zahl($u['sponsoren'])" icon="gift" :hint="$u['sponsorings_gesamt'].' Zusagen'" />
        <x-stat label="Ø Spende je Läufer" :value="euro($u['spende_schnitt'])" />
        <x-stat label="Spende je Runde" :value="euro($u['spende_pro_runde'])" :hint="'Festbeträge '.euro($u['fest']).' · Rundenbeträge '.euro($u['rundenbetrag'])" />
        <x-stat label="Teams" :value="zahl($u['teams'])" icon="team" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card title="Verteilung" class="lg:col-span-1">
            <x-chart :config="['type' => 'doughnut', 'euro' => true, 'labels' => $projekte->pluck('label'), 'datasets' => [['label' => 'Betrag', 'data' => $projekte->pluck('betrag')]]]" />
            <dl class="mt-4 space-y-1 text-sm">
                <div class="flex justify-between"><dt>Projekte</dt><dd class="tabular-nums">{{ euro($u['projekte']) }}</dd></div>
                @if ($u['abgabe'])<div class="flex justify-between"><dt>Soziale Abgabe</dt><dd class="tabular-nums">{{ euro($u['abgabe']) }}</dd></div>@endif
                @if ($u['klassenkasse'])<div class="flex justify-between"><dt>Klassenkassen</dt><dd class="tabular-nums">{{ euro($u['klassenkasse']) }}</dd></div>@endif
            </dl>
        </x-card>
        <x-card title="Spenden und Runden nach Altersgruppe" class="lg:col-span-2">
            <x-chart :config="['type' => 'bar', 'euro' => true, 'labels' => $altersgruppen->pluck('label'), 'datasets' => [['label' => 'Spenden', 'data' => $altersgruppen->pluck('spende')]]]" height="h-56" />
            <x-chart :config="['type' => 'bar', 'labels' => $altersgruppen->pluck('label'), 'datasets' => [['label' => 'Runden', 'data' => $altersgruppen->pluck('runden'), 'color' => '#002561']]]" height="h-40" class="mt-4" />
        </x-card>
        <x-card title="Wie viele Runden?" subtitle="Anzahl Läufer je Rundenbereich" class="lg:col-span-2">
            <x-chart :config="['type' => 'bar', 'labels' => collect($histogramm)->pluck('label'), 'datasets' => [['label' => 'Läufer', 'data' => collect($histogramm)->pluck('anzahl'), 'color' => '#2c519f']]]" />
        </x-card>
        <x-card title="Nach Kategorie">
            <x-chart :config="['type' => 'doughnut', 'labels' => $kategorien->pluck('label'), 'datasets' => [['label' => 'Läufer', 'data' => $kategorien->pluck('anzahl')]]]" />
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @foreach (['Meiste Runden' => [$topRunden, 'runden'], 'Höchste Spenden' => [$topSpende, 'spende']] as $titel => [$liste, $feld])
            <x-card :title="$titel" :padding="false">
                <ol class="divide-y divide-slate-100 text-sm">
                    @foreach ($liste as $i => $z)
                        <li class="flex items-center gap-3 px-4 py-2">
                            <span class="w-5 text-right font-bold text-esz-600">{{ $i + 1 }}</span>
                            <span class="flex-1">{{ $z['name'] }} <span class="text-xs text-slate-400">{{ $z['altersgruppe'] }}</span></span>
                            <span class="tabular-nums font-semibold">{{ $feld === 'spende' ? euro($z['spende']) : $z['runden'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        @endforeach
        <x-card title="Teams (Runden)" :padding="false">
            <ol class="divide-y divide-slate-100 text-sm">
                @foreach ($topTeams as $i => $t)
                    <li class="flex items-center gap-3 px-4 py-2">
                        <span class="w-5 text-right font-bold text-esz-600">{{ $i + 1 }}</span>
                        <span class="flex-1">{{ $t['name'] }} <span class="text-xs text-slate-400">{{ $t['mitglieder'] }} Läufer</span></span>
                        <span class="tabular-nums font-semibold">{{ $t['runden'] }}</span>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>
</x-layouts.app>
