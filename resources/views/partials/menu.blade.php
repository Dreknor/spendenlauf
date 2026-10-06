@php $u = auth()->user(); @endphp
<x-nav-link :href="route('home')" icon="home" :active="request()->routeIs('home')">Start</x-nav-link>
<x-nav-link :href="route('laeufer.index')" icon="runner" :active="request()->routeIs('laeufer.*')">{{ $u->can('edit laeufer') ? 'Läufer' : 'Meine Läufer' }}</x-nav-link>
<x-nav-link :href="route('teams.index')" icon="team" :active="request()->routeIs('teams.*')">Teams</x-nav-link>
<x-nav-link :href="route('sponsoren.index')" icon="heart" :active="request()->routeIs('sponsoren.*')">Sponsoren</x-nav-link>
<x-nav-link :href="route('sponsorings.index')" icon="gift" :active="request()->routeIs('sponsorings.*')">Spenden</x-nav-link>
<x-nav-link :href="route('projects.index')" icon="folder" :active="request()->routeIs('projects.*')">Projekte</x-nav-link>

@if ($u->canAny(['schnellerfassung', 'edit runden', 'import export', 'edit startnummer']))
    <p class="px-3 pt-5 pb-1 text-xs font-semibold uppercase tracking-wider text-navy-300">Lauftag</p>
    @can('schnellerfassung')
        <x-nav-link :href="route('schnellerfassung.create')" icon="bolt" :active="request()->routeIs('schnellerfassung.*')">Schnellerfassung</x-nav-link>
    @endcan
    @can('edit runden')
        <x-nav-link :href="route('runden.index')" icon="clock" :active="request()->routeIs('runden.*')">Runden eingeben</x-nav-link>
    @endcan
    @can('import export')
        <x-nav-link :href="route('import.create')" icon="upload" :active="request()->routeIs('import.*')">Runden importieren</x-nav-link>
    @endcan
    @can('edit startnummer')
        <x-nav-link :href="route('startnummern.index')" icon="hashtag" :active="request()->routeIs('startnummern.*')">Startnummern</x-nav-link>
    @endcan
@endif

@if ($u->canAny(['show auswertung', 'manage abrechnung']))
    <p class="px-3 pt-5 pb-1 text-xs font-semibold uppercase tracking-wider text-navy-300">Auswertung</p>
    @can('show auswertung')
        <x-nav-link :href="route('auswertung.uebersicht')" icon="chart" :active="request()->routeIs('auswertung.*') && ! request()->routeIs('auswertung.klasse*')">Statistiken</x-nav-link>
        <x-nav-link :href="route('auswertung.klassen')" icon="academic" :active="request()->routeIs('auswertung.klasse*')">Klassenauswertung</x-nav-link>
    @endcan
    @can('manage abrechnung')
        <x-nav-link :href="route('abrechnung.index')" icon="banknotes" :active="request()->routeIs('abrechnung.*')">Abrechnung</x-nav-link>
    @endcan
@endif

@if ($u->canAny(['edit event', 'edit user', 'import export']))
    <p class="px-3 pt-5 pb-1 text-xs font-semibold uppercase tracking-wider text-navy-300">Verwaltung</p>
    @can('edit event')
        <x-nav-link :href="route('events.index')" icon="calendar" :active="request()->routeIs('events.*', 'klassen.*')">Veranstaltungen</x-nav-link>
    @endcan
    @can('edit user')
        <x-nav-link :href="route('users.index')" icon="users" :active="request()->routeIs('users.*')">Benutzer</x-nav-link>
    @endcan
    @can('import export')
        <div x-data="{ open: false }">
            <button type="button" @click="open = !open" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-navy-100 hover:bg-white/10 hover:text-white">
                <x-icon name="download" class="size-5" /> Exporte
            </button>
            <div x-cloak x-show="open" class="ml-8 space-y-1 text-sm">
                <a class="block py-1 text-navy-100 hover:text-white" href="{{ route('export.laeufer') }}">Läufer (Excel)</a>
                <a class="block py-1 text-navy-100 hover:text-white" href="{{ route('export.sponsoren') }}">Sponsoren (Excel)</a>
                <a class="block py-1 text-navy-100 hover:text-white" href="{{ route('export.projekte') }}">Projekte & Verteilung (Excel)</a>
            </div>
        </div>
    @endcan
@endif
