<x-layouts.app title="Auswertung Sponsoren">
    <x-page-header title="Sponsoren" :subtitle="$event->name">
        <a href="{{ route('auswertung.export', 'sponsoren') }}" class="btn-light"><x-icon name="download" class="size-4" /> Excel</a>
    </x-page-header>
    @include('auswertung._nav')
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Sponsoren" :value="zahl($u['sponsoren'])" accent />
        <x-stat label="Zusagen" :value="zahl($u['sponsorings_gesamt'])" />
        <x-stat label="Ø je Sponsor" :value="euro($u['sponsoren'] ? intdiv($u['brutto'], $u['sponsoren']) : 0)" />
        <x-stat label="Festbeträge / Rundenbeträge" :value="$u['brutto'] ? round($u['fest'] / $u['brutto'] * 100).' / '.round($u['rundenbetrag'] / $u['brutto'] * 100).' %' : '–'" />
    </div>
    <div class="my-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Anzahl Sponsoren nach Spendenhöhe">
            <x-chart :config="['type' => 'bar', 'labels' => collect($verteilung)->pluck('label'), 'datasets' => [['label' => 'Sponsoren', 'data' => collect($verteilung)->pluck('anzahl')]]]" />
        </x-card>
        <x-card title="Summe nach Spendenhöhe">
            <x-chart :config="['type' => 'bar', 'euro' => true, 'labels' => collect($verteilung)->pluck('label'), 'datasets' => [['label' => 'Summe', 'data' => collect($verteilung)->pluck('summe'), 'color' => '#002561']]]" />
        </x-card>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Sponsor</th><th>Ort</th><th class="num">Zusagen</th><th class="num">Betrag</th><th class="num">bezahlt</th></tr></thead>
            <tbody>
            @foreach ($sponsoren as $i => $s)
                <tr>
                    <td class="text-slate-400">{{ $i + 1 }}</td>
                    <td><a href="{{ route('sponsoren.edit', $s['id']) }}">{{ $s['name'] }}</a></td>
                    <td class="text-slate-600">{{ $s['ort'] ?? '–' }}</td>
                    <td class="num">{{ $s['sponsorings'] }}</td>
                    <td class="num font-semibold">{{ euro($s['soll']) }}</td>
                    <td class="num">{{ euro($s['ist']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
