@props(['title' => null, 'bare' => false])
<!DOCTYPE html>
<html lang="de">
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
<body class="min-h-screen">
@unless ($bare)
    <header class="bg-white shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ url('/') }}"><img src="{{ asset(config('spendenlauf.logo')) }}" alt="{{ config('spendenlauf.schule') }}" class="h-10 w-auto sm:h-12"></a>
            @if ($aktuellesEvent)
                <div class="text-right">
                    <p class="font-semibold text-navy-800">{{ $aktuellesEvent->name }}</p>
                    <p class="text-sm text-slate-500">{{ $aktuellesEvent->datum->translatedFormat('d. F Y') }}</p>
                </div>
            @endif
        </div>
        <div class="h-1 bg-gradient-to-r from-esz-600 via-esz-600 to-navy-800"></div>
    </header>
@endunless
<main @class(['mx-auto max-w-5xl px-4 py-6 sm:py-10' => ! $bare, 'p-3' => $bare])>
    <x-flash />
    {{ $slot }}
</main>
@unless ($bare)
    <footer class="mx-auto max-w-5xl px-4 pb-8 text-xs text-slate-400">
        {{ config('spendenlauf.schule') }} ·
        <a class="text-slate-400" href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutz</a> ·
        <a class="text-slate-400" href="{{ config('spendenlauf.impressum_link') }}" target="_blank" rel="noopener">Impressum</a>
    </footer>
@endunless
@stack('scripts')
</body>
</html>
