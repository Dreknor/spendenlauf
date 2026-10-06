@props(['title' => null])
<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon-esz.png') }}">
    <meta name="theme-color" content="#002561">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full">
<div x-data="{ menu: false }" class="min-h-full">
    {{-- Mobile Topbar --}}
    <div class="sticky top-0 z-30 flex items-center gap-3 bg-navy-800 px-4 py-3 text-white shadow lg:hidden">
        <button type="button" @click="menu = true" class="-m-1 p-1" aria-label="Menü öffnen">
            <x-icon name="menu" class="size-6" />
        </button>
        <a href="{{ route('home') }}" class="font-semibold text-white no-underline">{{ config('app.name') }}</a>
        @if ($aktuellesEvent)
            <span class="ml-auto truncate text-xs text-navy-200">{{ $aktuellesEvent->datum->format('d.m.Y') }}</span>
        @endif
    </div>

    {{-- Overlay mobil --}}
    <div x-cloak x-show="menu" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="menu = false"></div>

    {{-- Sidebar --}}
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-navy-800 transition-transform lg:translate-x-0">
        <div class="bg-white px-5 py-4">
            <a href="{{ config('spendenlauf.logo_link') }}" target="_blank" rel="noopener">
                <img src="{{ asset(config('spendenlauf.logo')) }}" alt="{{ config('spendenlauf.schule') }}" class="h-12 w-auto">
            </a>
        </div>
        <div class="border-b border-white/10 px-5 py-3">
            <p class="text-xs uppercase tracking-wider text-navy-300">Spendenlauf</p>
            @if ($aktuellesEvent)
                <p class="font-semibold text-white">{{ $aktuellesEvent->name }}</p>
                <p class="text-sm text-navy-200">{{ $aktuellesEvent->datum->translatedFormat('l, d. F Y') }}</p>
            @else
                <p class="text-sm text-navy-200">Keine Veranstaltung angelegt</p>
            @endif
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Hauptnavigation">
            @include('partials.menu')
        </nav>
        <div class="border-t border-white/10 px-3 py-3">
            <x-nav-link :href="route('profil')" icon="user" :active="request()->routeIs('profil')">{{ auth()->user()->name }}</x-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-navy-100 hover:bg-white/10 hover:text-white">
                    <x-icon name="logout" class="size-5" /> Abmelden
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-72">
        @can('edit event')
            @if ($aktuellesEvent && ! $aktuellesEvent->aktiv)
                <div class="bg-amber-100 px-4 py-2 text-center text-sm text-amber-900">
                    Sie sehen die Daten von <strong>{{ $aktuellesEvent->name }}</strong> (nicht aktive Veranstaltung).
                    <a href="{{ route('events.index') }}">Veranstaltung wechseln</a>
                </div>
            @endif
        @endcan
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <x-flash />
            {{ $slot }}
        </main>
        <footer class="mx-auto max-w-7xl px-4 pb-8 text-xs text-slate-400 sm:px-6 lg:px-8">
            <a class="text-slate-400" href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutz</a>
            · <a class="text-slate-400" href="{{ config('spendenlauf.impressum_link') }}" target="_blank" rel="noopener">Impressum</a>
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
