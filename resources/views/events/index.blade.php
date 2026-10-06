<x-layouts.app title="Veranstaltungen">
    <x-page-header title="Veranstaltungen" subtitle="Jeder Spendenlauf ist eine Veranstaltung mit eigenen Läufern, Spenden, Klassen und Einstellungen. Sponsoren und Benutzerkonten bleiben erhalten.">
        <a href="{{ route('events.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Neuen Lauf vorbereiten</a>
    </x-page-header>
    @if ($events->isEmpty())
        <x-empty message="Noch keine Veranstaltung angelegt." />
    @else
        <div class="space-y-4">
            @foreach ($events as $e)
                <div @class(['card flex flex-wrap items-center gap-4 p-4 sm:p-5', 'ring-2! ring-esz-500!' => $aktuell?->id === $e->id])>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg">{{ $e->name }}</h2>
                            @if ($e->aktiv)<x-badge color="green">aktiv</x-badge>@endif
                            @if ($aktuell?->id === $e->id && ! $e->aktiv)<x-badge color="amber">angezeigt</x-badge>@endif
                        </div>
                        <p class="text-sm text-slate-500">
                            {{ $e->datum->translatedFormat('d. F Y') }} · {{ $e->anzahl_laeufer }} Läufer · {{ $e->anzahl_sponsorings }} Spenden · {{ $e->anzahl_klassen }} Klassen
                            · Abgabe {{ prozent($e->soziale_abgabe_prozent) }} % · Klassenkasse {{ prozent($e->klassenkasse_prozent) }} %
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('events.edit', $e) }}" class="btn-light btn-sm"><x-icon name="cog" class="size-4" /> Einstellungen</a>
                        <a href="{{ route('klassen.index', $e) }}" class="btn-light btn-sm"><x-icon name="academic" class="size-4" /> Klassen</a>
                        @if ($aktuell?->id !== $e->id)
                            <form method="POST" action="{{ route('events.wechseln', $e) }}">@csrf<button class="btn-light btn-sm">Ansehen</button></form>
                        @endif
                        @unless ($e->aktiv)
                            <form method="POST" action="{{ route('events.aktivieren', $e) }}" onsubmit="return confirm('Diese Veranstaltung für alle Benutzer aktivieren?')">@csrf<button class="btn-secondary btn-sm">Aktivieren</button></form>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
