<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon-esz.png') }}">
    <meta name="theme-color" content="#002561">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
<header class="relative isolate overflow-hidden">
    <img src="{{ asset('img/header.jpg') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover">
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-navy-900/90 via-navy-800/60 to-navy-800/20"></div>
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
        <a href="{{ config('spendenlauf.logo_link') }}" class="rounded-lg bg-white px-3 py-2 shadow">
            <img src="{{ asset(config('spendenlauf.logo')) }}" alt="{{ config('spendenlauf.schule') }}" class="h-10 w-auto">
        </a>
        <nav class="flex gap-2">
            @auth
                <a href="{{ route('home') }}" class="btn-primary">Zur Verwaltung</a>
            @else
                <a href="{{ route('login') }}" class="btn-light">Anmelden</a>
            @endauth
        </nav>
    </div>
    <div class="mx-auto max-w-5xl px-4 pt-24 pb-16 sm:pt-40 sm:pb-24">
        <p class="text-sm font-semibold uppercase tracking-widest text-esz-200">{{ config('spendenlauf.schule') }}</p>
        <h1 class="mt-2 text-4xl font-bold text-white! sm:text-6xl">
            {{ $aktuellesEvent?->name ?? 'Radebeuler Spendenlauf' }}
        </h1>
        @if ($aktuellesEvent)
            <p class="mt-3 text-xl text-white/90">{{ $aktuellesEvent->datum->translatedFormat('l, d. F Y') }}</p>
        @endif
        <p class="mt-6 max-w-2xl text-lg text-white/90">
            Schülerinnen und Schüler, Eltern, Lehrkräfte, Freunde und Gäste laufen Runden für gute Zwecke.
            Sponsoren unterstützen jede gelaufene Runde oder mit einem festen Betrag.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('laeufer.create') }}" class="btn-primary btn-lg">Läufer anmelden</a>
            @else
                <a href="{{ route('register') }}" class="btn-primary btn-lg">Registrieren & Läufer anmelden</a>
                <a href="{{ route('login') }}" class="btn-light btn-lg">Ich habe schon ein Konto</a>
            @endauth
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-12">
    <div class="grid gap-6 sm:grid-cols-3">
        <div class="card p-6">
            <x-icon name="runner" class="size-8 text-esz-600" />
            <h2 class="mt-3 text-lg">1. Anmelden</h2>
            <p class="mt-1 text-sm text-slate-600">Konto anlegen und sich selbst oder die eigenen Kinder als Läufer anmelden – gern auch als Team.</p>
        </div>
        <div class="card p-6">
            <x-icon name="heart" class="size-8 text-esz-600" />
            <h2 class="mt-3 text-lg">2. Sponsoren finden</h2>
            <p class="mt-1 text-sm text-slate-600">Jeder Läufer erhält einen persönlichen Link, über den Sponsoren ihre Spende selbst zusagen können.</p>
        </div>
        <div class="card p-6">
            <x-icon name="trophy" class="size-8 text-esz-600" />
            <h2 class="mt-3 text-lg">3. Laufen!</h2>
            <p class="mt-1 text-sm text-slate-600">Am Lauftag zählt jede Runde. Danach erhalten die Sponsoren eine Mail mit ihrem Spendenbetrag.</p>
        </div>
    </div>

    <div class="mt-10 flex flex-wrap gap-3">
        <a href="{{ route('projects.index') }}" class="btn-secondary">Unsere Projekte</a>
        @if ($aktuellesEvent)
            <a href="{{ route('stats') }}" class="btn-light">Live-Statistik</a>
        @endif
    </div>
</main>

<footer class="border-t border-slate-100 py-6 text-center text-xs text-slate-400">
    {{ config('spendenlauf.schule') }} ·
    <a class="text-slate-400" href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutz</a> ·
    <a class="text-slate-400" href="{{ config('spendenlauf.impressum_link') }}" target="_blank" rel="noopener">Impressum</a>
</footer>
</body>
</html>
