@props(['title' => null, 'wide' => false])
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
</head>
<body class="h-full bg-gradient-to-br from-navy-800 to-navy-900">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-10">
    <a href="{{ url('/') }}" class="mb-6 rounded-xl bg-white px-6 py-3 shadow">
        <img src="{{ asset(config('spendenlauf.logo')) }}" alt="{{ config('spendenlauf.schule') }}" class="h-12 w-auto">
    </a>
    <div @class(['w-full rounded-2xl bg-white p-6 shadow-xl sm:p-8', 'max-w-md' => ! $wide, 'max-w-2xl' => $wide])>
        @if ($title)<h1 class="mb-6 text-2xl">{{ $title }}</h1>@endif
        <x-flash />
        {{ $slot }}
    </div>
    <p class="mt-6 text-xs text-navy-200">
        <a class="text-navy-200 hover:text-white" href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutz</a>
        · <a class="text-navy-200 hover:text-white" href="{{ config('spendenlauf.impressum_link') }}" target="_blank" rel="noopener">Impressum</a>
    </p>
</div>
</body>
</html>
