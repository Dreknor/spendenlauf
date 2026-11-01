<x-layouts.app :title="$laeufer->name">
    <x-page-header :title="$laeufer->name" :subtitle="collect([$laeufer->kategorie?->label(), $laeufer->schulklasse ? 'Klasse '.$laeufer->schulklasse->name : null, $laeufer->team ? 'Team '.$laeufer->team->name : null])->filter()->join(' · ')">
        <span class="inline-flex items-center rounded-lg bg-navy-800 px-3 py-2 text-sm font-bold text-white">Startnummer {{ $laeufer->startnummer ?? '–' }}</span>
        @if ($event->laufVorbei() && $laeufer->runden > 0)
            <a href="{{ route('laeufer.bescheinigung', $laeufer) }}" class="btn-light" target="_blank"><x-icon name="printer" class="size-4" /> Urkunde</a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Spenden" :value="euro($bilanz->laeuferSpende($laeufer->id))" icon="heart" accent
                :hint="($bilanz->laeufer[$laeufer->id]['teamanteil'] ?? 0) > 0 ? 'inkl. '.euro($bilanz->laeufer[$laeufer->id]['teamanteil']).' Anteil an Team-Spenden' : null" />
        <x-stat label="Runden" :value="zahl($laeufer->runden)" icon="clock" />
        <x-stat label="Strecke" :value="zahl($event->km($laeufer->runden), 1).' km'" icon="runner" />
        <x-stat label="Sponsoren" :value="zahl($laeufer->sponsorings->count())" icon="gift" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($event->anmeldungOffen())
                @php $link = route('public.sponsor.show', $laeufer->uuid); @endphp
                <x-card title="Persönlicher Sponsor-Link" subtitle="Diesen Link an Familie, Freunde, Nachbarn oder Firmen schicken – Sponsoren können ihre Spende dort selbst eintragen.">
                    <div x-data="{ kopiert: false }" class="flex flex-col gap-2 sm:flex-row">
                        <input type="text" readonly value="{{ $link }}" class="mt-0! font-mono text-xs" @focus="$el.select()" aria-label="Sponsor-Link">
                        <button type="button" class="btn-secondary shrink-0" @click="navigator.clipboard.writeText(@js($link)); kopiert = true; setTimeout(() => kopiert = false, 2000)">
                            <x-icon name="link" class="size-4" /> <span x-text="kopiert ? 'Kopiert!' : 'Kopieren'">Kopieren</span>
                        </button>
                    </div>
                    @php $text = rawurlencode("Ich laufe beim {$event->name} am {$event->datum->format('d.m.Y')} für gute Zwecke. Unterstützt du mich? $link"); @endphp
                    <div class="mt-3 flex flex-wrap gap-2 text-sm">
                        <a class="btn-light btn-sm" href="https://wa.me/?text={{ $text }}" target="_blank" rel="noopener">WhatsApp</a>
                        <a class="btn-light btn-sm" href="mailto:?subject={{ rawurlencode('Sponsor für den '.$event->name) }}&body={{ $text }}">E-Mail</a>
                    </div>
                </x-card>
            @endif

            <x-card title="Spenden für {{ $laeufer->vorname }}" :padding="false">
                <x-slot:actions>
                    @if ($event->anmeldungOffen() || auth()->user()->can('edit sponsorings'))
                        <a href="{{ route('sponsorings.create', ['laeufer' => $laeufer->id]) }}" class="btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Spende eintragen</a>
                    @endif
                </x-slot:actions>
                @if ($laeufer->sponsorings->isEmpty())
                    <div class="p-6"><x-empty message="Noch keine Sponsoren. Teilen Sie den Sponsor-Link oder tragen Sie eine Spende ein." /></div>
                @else
                    @include('sponsorings._tabelle', ['sponsorings' => $laeufer->sponsorings, 'zeigeZiel' => false])
                @endif
            </x-card>

            @if ($teamSponsorings->isNotEmpty())
                <x-card title="Spenden für das Team „{{ $laeufer->team->name }}“" subtitle="Diese Spenden werden nach den Runden aller Teammitglieder berechnet." :padding="false">
                    @include('sponsorings._tabelle', ['sponsorings' => $teamSponsorings, 'zeigeZiel' => false])
                </x-card>
            @endif
        </div>

        <div class="space-y-6">
            <x-card title="Team">
                @if ($laeufer->team)
                    <p class="font-semibold">{{ $laeufer->team->name }}</p>
                    <p class="text-sm text-slate-500">{{ $laeufer->team->laeufer->count() }} Mitglieder · {{ $laeufer->team->laeufer->sum('runden') }} Runden</p>
                    <form method="POST" action="{{ route('laeufer.team.leave', $laeufer) }}" class="mt-3" onsubmit="return confirm('Team wirklich verlassen?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-light btn-sm">Team verlassen</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('laeufer.team.join', $laeufer) }}" class="space-y-3" x-data="{ neu: false }">
                        @csrf
                        <div x-show="!neu"><x-select name="team_id" label="Team beitreten" :options="$teams->pluck('name', 'id')" placeholder="Team wählen" /></div>
                        <div x-show="neu" x-cloak><x-input name="teamname" label="Neues Team gründen" /></div>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn-secondary btn-sm">Speichern</button>
                            <button type="button" class="btn-light btn-sm" @click="neu = !neu" x-text="neu ? 'bestehendes Team' : 'neues Team'">neues Team</button>
                        </div>
                    </form>
                @endif
            </x-card>

            <x-card title="Verwaltet von" subtitle="Alle hier genannten Konten sehen und bearbeiten den Läufer.">
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center justify-between gap-2">
                        <span>{{ $laeufer->besitzer?->name ?? '–' }} <span class="text-xs text-slate-400">angemeldet</span></span>
                    </li>
                    @foreach ($laeufer->mitverwalter as $m)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $m->name }}</span>
                            @if (auth()->user()->is($m) || auth()->user()->can('update', $laeufer))
                                <form method="POST" action="{{ route('mitverwaltung.entfernen', [$laeufer, $m]) }}" onsubmit="return confirm('Mitverwaltung beenden?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">entfernen</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($laeufer->mitverwaltungAnfragen->isNotEmpty() && (auth()->user()->can('edit laeufer') || (int) $laeufer->verwaltet_von === auth()->id()))
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Offene Anfragen</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach ($laeufer->mitverwaltungAnfragen as $anfrage)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-amber-50 p-2">
                                <span>{{ $anfrage->user->name }} <span class="text-xs text-slate-500">{{ $anfrage->user->email }}</span></span>
                                <span class="flex gap-1">
                                    <form method="POST" action="{{ route('mitverwaltung.annehmen', $anfrage) }}">@csrf<button class="btn-success btn-sm">Bestätigen</button></form>
                                    <form method="POST" action="{{ route('mitverwaltung.ablehnen', $anfrage) }}">@csrf<button class="btn-light btn-sm">Ablehnen</button></form>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('update', $laeufer)
                    @if ($laeufer->mitverwaltungEinladungen->isNotEmpty())
                        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Eingeladen</p>
                        <ul class="mt-2 space-y-2 text-sm">
                            @foreach ($laeufer->mitverwaltungEinladungen as $einladung)
                                <li class="flex items-center justify-between gap-2">
                                    <span>{{ $einladung->email }} <span class="text-xs text-slate-400">seit {{ $einladung->created_at->format('d.m.') }}</span></span>
                                    <form method="POST" action="{{ route('mitverwaltung.einladung.zurueckziehen', $einladung) }}">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 hover:underline">zurückziehen</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('mitverwaltung.hinzufuegen', $laeufer) }}" class="mt-4 flex items-end gap-2">
                        @csrf
                        <x-input name="email" type="email" :label="auth()->user()->can('edit laeufer') ? 'Weiteres Konto zuordnen' : 'Weiteres Konto einladen'" placeholder="E-Mail-Adresse" class="flex-1" />
                        <button type="submit" class="btn-light">{{ auth()->user()->can('edit laeufer') ? 'Zuordnen' : 'Einladen' }}</button>
                    </form>
                    <p class="hint">
                        @can('edit laeufer')
                            Ordnet ein vorhandenes Konto direkt zu.
                        @else
                            Die Person erhält eine E-Mail und verwaltet erst mit, wenn sie die Einladung mit einem Konto zu dieser Adresse annimmt.
                        @endcan
                    </p>
                @endcan
            </x-card>

            <x-card title="Daten bearbeiten">
                @if ($event->laufVorbei() && ! auth()->user()->can('edit laeufer'))
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-slate-500">Geburtsdatum</dt><dd>{{ $laeufer->geburtsdatum?->format('d.m.Y') ?? '–' }}</dd></div>
                        <div><dt class="text-slate-500">E-Mail</dt><dd>{{ $laeufer->email ?? '–' }}</dd></div>
                    </dl>
                    <p class="hint mt-3">Nach dem Lauf können die Daten nicht mehr geändert werden.</p>
                @else
                    <form method="POST" action="{{ route('laeufer.update', $laeufer) }}" class="space-y-4">
                        @csrf @method('PUT')
                        @include('laeufer._form')
                        <button type="submit" class="btn-primary w-full">Speichern</button>
                    </form>
                @endif
            </x-card>

            @can('delete', $laeufer)
                <x-delete-button :action="route('laeufer.destroy', $laeufer)" label="Läufer abmelden" confirm="Läufer wirklich abmelden?" />
            @endcan
        </div>
    </div>
</x-layouts.app>
