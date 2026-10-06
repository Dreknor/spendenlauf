<x-layouts.app title="Start">
    @if (! $event)
        <x-page-header title="Willkommen" />
        <x-empty message="Es ist noch keine Veranstaltung angelegt.">
            @can('edit event')
                <a href="{{ route('events.create') }}" class="btn-primary">Veranstaltung anlegen</a>
            @endcan
        </x-empty>
    @else
        <x-page-header :title="'Hallo '.auth()->user()->vorname.'!'" :subtitle="$event->name.' am '.$event->datum->translatedFormat('l, d. F Y')">
            @if ($event->anmeldungOffen())
                <a href="{{ route('laeufer.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Läufer anmelden</a>
            @endif
        </x-page-header>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-stat label="Spenden bisher" :value="euro($bilanz->bruttoCent)" icon="heart" accent />
            <x-stat label="Läufer" :value="zahl($anzahlLaeufer)" icon="runner" />
            <x-stat label="Teams" :value="zahl($anzahlTeams)" icon="team" />
            <x-stat label="Sponsoren" :value="zahl($anzahlSponsoren)" icon="gift" />
        </div>

        @if ($vorjahresLaeufer->isNotEmpty())
            <x-card title="Wieder dabei?" subtitle="Diese Läufer hatten Sie bei einem früheren Spendenlauf angemeldet." class="mt-6">
                <form method="POST" action="{{ route('laeufer.uebernehmen') }}" class="space-y-3">
                    @csrf
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($vorjahresLaeufer as $alt)
                            <x-checkbox name="laeufer[]" :value="(string) $alt->id" :label="$alt->name" checked />
                        @endforeach
                    </div>
                    <button type="submit" class="btn-secondary">Ausgewählte für {{ $event->jahr() }} anmelden</button>
                </form>
            </x-card>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <x-card title="Meine Läufer" class="lg:col-span-2" :padding="false">
                <x-slot:actions>
                    <a href="{{ route('laeufer.index') }}" class="btn-light btn-sm">Alle anzeigen</a>
                </x-slot:actions>
                @if ($meineLaeufer->isEmpty())
                    <div class="p-6"><x-empty message="Sie haben noch keine Läufer angemeldet." /></div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($meineLaeufer as $l)
                            <li class="flex flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
                                <span class="inline-flex size-10 items-center justify-center rounded-full bg-esz-50 font-bold text-esz-700">{{ $l->startnummer }}</span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('laeufer.edit', $l) }}" class="font-semibold">{{ $l->name }}</a>
                                    <p class="text-xs text-slate-500">
                                        {{ $l->kategorie?->label() }}@if ($l->schulklasse) · Klasse {{ $l->schulklasse->name }}@endif
                                        @if ($l->team) · Team {{ $l->team->name }}@endif
                                    </p>
                                </div>
                                <div class="text-right text-sm">
                                    <p class="font-semibold tabular-nums">{{ euro($bilanz->laeuferSpende($l->id)) }}</p>
                                    <p class="text-xs text-slate-500">{{ $l->runden }} Runden</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="So funktioniert's">
                <ol class="space-y-4 text-sm">
                    <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-navy-800 text-xs font-bold text-white">1</span>
                        <span><a href="{{ route('laeufer.create') }}">Läufer anmelden</a> – sich selbst oder die eigenen Kinder.</span></li>
                    <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-navy-800 text-xs font-bold text-white">2</span>
                        <span>Optional ein <a href="{{ route('teams.index') }}">Team</a> gründen oder einem offenen Team beitreten.</span></li>
                    <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-navy-800 text-xs font-bold text-white">3</span>
                        <span>Den persönlichen <strong>Sponsor-Link</strong> (auf der Seite des Läufers) an Familie, Freunde und Firmen schicken – oder Sponsoren selbst <a href="{{ route('sponsoren.create') }}">eintragen</a>.</span></li>
                    <li class="flex gap-3"><span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-esz-600 text-xs font-bold text-white">4</span>
                        <span>Am {{ $event->datum->format('d.m.') }} laufen! Die Sponsoren erhalten danach eine Mail mit ihrem Betrag.</span></li>
                </ol>
                @if ($event->hatSozialeAbgabe() || $event->hatKlassenkasse())
                    <div class="mt-5 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                        @if ($event->hatSozialeAbgabe())
                            <p>{{ prozent($event->soziale_abgabe_prozent) }} % aller Spenden gehen an {{ $event->soziale_abgabe_empfaenger }}.</p>
                        @endif
                        @if ($event->hatKlassenkasse())
                            <p>{{ prozent($event->klassenkasse_prozent) }} % der von Schülerinnen und Schülern erlaufenen Spenden gehen in die jeweilige Klassenkasse.</p>
                        @endif
                    </div>
                @endif
            </x-card>
        </div>
    @endif
</x-layouts.app>
