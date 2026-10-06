<x-layouts.app :title="'Klasse '.$klasse['name']">
    <x-page-header :title="'Klasse '.$klasse['name']" :subtitle="$klasse['klassenleitung'] ? 'Klassenleitung: '.$klasse['klassenleitung'] : $event->name">
        <a href="{{ route('auswertung.klassen') }}" class="btn-light">Alle Klassen</a>
    </x-page-header>
    @include('auswertung._nav')
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Klassenkasse" :value="euro($klasse['kasse'])" accent :hint="'davon bezahlt '.euro($klasse['kasse_bezahlt'])" />
        <x-stat label="Spenden" :value="euro($klasse['brutto'])" />
        <x-stat label="Runden" :value="zahl($klasse['runden'])" :hint="'Ø '.zahl($klasse['runden_schnitt'], 1)" />
        <x-stat label="Gelaufen" :value="$klasse['teilnehmer'].' / '.$klasse['schueler']" />
    </div>
    <div class="table-wrap mt-6">
        <table class="table">
            <thead><tr><th>#</th><th>Nr.</th><th>Name</th><th>Team</th><th class="num">Runden</th><th class="num">km</th><th class="num">Spenden</th><th class="num">davon Team</th></tr></thead>
            <tbody>
            @foreach ($zeilen as $i => $z)
                <tr>
                    <td class="text-slate-400">{{ $i + 1 }}</td>
                    <td class="tabular-nums">{{ $z['startnummer'] }}</td>
                    <td><a href="{{ route('laeufer.edit', $z['id']) }}">{{ $z['name'] }}</a></td>
                    <td class="text-slate-600">{{ $z['team'] }}</td>
                    <td class="num">{{ $z['runden'] }}</td>
                    <td class="num">{{ zahl($z['km'], 1) }}</td>
                    <td class="num">{{ euro($z['spende']) }}</td>
                    <td class="num text-slate-500">{{ $z['teamanteil'] ? euro($z['teamanteil']) : '–' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
