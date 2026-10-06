@php
    $tabs = [
        'auswertung.uebersicht' => 'Übersicht',
        'auswertung.laeufer' => 'Läufer',
        'auswertung.teams' => 'Teams',
        'auswertung.altersgruppen' => 'Altersgruppen',
        'auswertung.gruppen' => 'Weitere Gruppen',
        'auswertung.klassen' => 'Klassen',
        'auswertung.sponsoren' => 'Sponsoren',
        'auswertung.projekte' => 'Verteilung',
        'auswertung.jahresvergleich' => 'Jahresvergleich',
    ];
@endphp
<nav class="-mx-4 mb-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Auswertungen">
    <div class="flex min-w-max gap-1 border-b border-slate-200">
        @foreach ($tabs as $route => $label)
            <a href="{{ route($route) }}" @class([
                '-mb-px border-b-2 px-3 py-2 text-sm font-medium no-underline',
                'border-esz-600 text-esz-700' => request()->routeIs($route) || ($route === 'auswertung.klassen' && request()->routeIs('auswertung.klasse')),
                'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => ! (request()->routeIs($route) || ($route === 'auswertung.klassen' && request()->routeIs('auswertung.klasse'))),
            ])>{{ $label }}</a>
        @endforeach
    </div>
</nav>
