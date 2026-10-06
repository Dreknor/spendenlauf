<x-layouts.app title="Abrechnung">
    <x-page-header title="Abrechnung" :subtitle="$event->name">
        <a href="{{ route('abrechnung.mails', ['typ' => 'aufforderung']) }}" class="btn-primary"><x-icon name="mail" class="size-4" /> Zahlungsaufforderungen</a>
        <a href="{{ route('abrechnung.mails', ['typ' => 'erinnerung']) }}" class="btn-light"><x-icon name="mail" class="size-4" /> Erinnerungen</a>
        <div x-data="{ open: false }" class="relative">
            <button type="button" class="btn-light" @click="open = !open"><x-icon name="download" class="size-4" /> Export</button>
            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-10 mt-1 w-64 rounded-lg bg-white py-1 shadow-lg ring-1 ring-slate-200">
                <a class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" href="{{ route('abrechnung.export', 'alle') }}">Alle Sponsoren</a>
                <a class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" href="{{ route('abrechnung.export', 'offen') }}">Offene Posten</a>
                <a class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" href="{{ route('abrechnung.export', 'grossspenden') }}">Über 300 € (Zuwendungsbestätigung)</a>
            </div>
        </div>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Soll" :value="euro($bilanz->bruttoCent)" icon="heart" accent />
        <x-stat label="Eingegangen" :value="euro($bilanz->bezahltCent)" icon="banknotes" :hint="$bilanz->bruttoCent ? round($bilanz->bezahltCent / $bilanz->bruttoCent * 100).' %' : null" />
        <x-stat label="Offen" :value="euro($bilanz->offenCent())" icon="clock" />
        <x-stat label="Sponsoren" :value="$konten->count()" icon="gift" :hint="($anzahl['bezahlt'] ?? 0).' bezahlt · '.(($anzahl['offen'] ?? 0) + ($anzahl['teilweise'] ?? 0)).' offen'" />
    </div>

    @if ($ohneMail || $ohneAufforderung)
        <div class="mt-4 flex flex-wrap gap-2 text-sm">
            @if ($ohneAufforderung)<a href="{{ route('abrechnung.index', ['mail' => 'ohne']) }}"><x-badge color="amber">{{ $ohneAufforderung }} noch ohne Zahlungsaufforderung</x-badge></a>@endif
            @if ($ohneMail)<a href="{{ route('abrechnung.index', ['mail' => 'keine_adresse']) }}"><x-badge color="red">{{ $ohneMail }} ohne E-Mail-Adresse (Brief nötig)</x-badge></a>@endif
        </div>
    @endif

    <x-search-form placeholder="Name, E-Mail, Nr." class="my-4">
        <x-select name="status" :options="collect(\App\Enums\ZahlungsStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :selected="request('status')" placeholder="alle Status" class="w-44" />
        <x-select name="sort" :options="['name' => 'nach Name', 'soll' => 'nach Betrag', 'offen' => 'nach offenem Betrag']" :selected="request('sort')" class="w-48" />
    </x-search-form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nr.</th><th>Sponsor</th><th>E-Mail</th><th class="num">Soll</th><th class="num">Bezahlt</th><th>Status</th><th>Aufforderung</th></tr></thead>
            <tbody>
            @forelse ($konten as $k)
                <tr>
                    <td class="tabular-nums text-slate-500">{{ $k['sponsor']->id }}</td>
                    <td><a href="{{ route('abrechnung.show', $k['sponsor']) }}" class="font-medium">{{ $k['sponsor']->name }}</a></td>
                    <td class="text-slate-600">{{ $k['sponsor']->email ?? '–' }}</td>
                    <td class="num">{{ euro($k['konto']->sollCent) }}</td>
                    <td class="num">{{ euro($k['konto']->istCent) }}</td>
                    <td><x-badge :color="$k['status']->farbe()">{{ $k['status']->label() }}</x-badge></td>
                    <td class="text-xs text-slate-500">
                        {{ $k['aufforderung']?->format('d.m.Y') ?? '–' }}
                        @if ($k['erinnerung'])<br>Erinnerung {{ $k['erinnerung']->format('d.m.Y') }}@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-500">Keine Einträge.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
