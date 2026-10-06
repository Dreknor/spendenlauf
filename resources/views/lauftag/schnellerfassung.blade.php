@php
    $alteSponsoren = collect(old('sponsoren', []))->values()->map(fn ($s) => array_merge([
        'sponsor_id' => '', 'sponsor_name' => '', 'anrede' => 'Frau', 'vorname' => '', 'nachname' => '', 'firmenname' => '', 'email' => '',
        'telefon' => '', 'strasse' => '', 'plz' => '', 'ort' => '', 'festBetrag' => '', 'rundenBetrag' => '', 'maxBetrag' => '', 'ziel' => 'laeufer', 'bar' => '',
    ], $s));
    $vorauswahlJson = $vorauswahl ? ['id' => $vorauswahl->id, 'name' => $vorauswahl->name, 'startnummer' => $vorauswahl->startnummer] : null;
@endphp
<x-layouts.app title="Schnellerfassung">
    <x-page-header title="Schnellerfassung" subtitle="Anmeldetisch: Läufer, Startnummer und Spenden in einem Schritt erfassen.">
        @if (session('letzte_startnummer'))
            <span class="rounded-lg bg-emerald-600 px-4 py-2 text-lg font-bold text-white">Letzte Startnummer: {{ session('letzte_startnummer') }}</span>
        @endif
    </x-page-header>

    <form method="POST" action="{{ route('schnellerfassung.store') }}" class="grid gap-6 xl:grid-cols-5"
          x-data="schnellerfassung(@js([
              'laeufer' => old('laeufer_id') ? ['id' => (int) old('laeufer_id'), 'name' => old('laeufer_name'), 'startnummer' => null] : $vorauswahlJson,
              'neu' => (bool) old('vorname'),
              'kategorie' => old('kategorie', ''),
              'sponsoren' => $alteSponsoren,
              'naechste' => $naechsteNummer,
          ]))">
        @csrf

        {{-- Läufer --}}
        <div class="space-y-6 xl:col-span-2">
            <x-card title="1. Läufer">
                <template x-if="laeufer">
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-esz-50 p-3 ring-1 ring-esz-200">
                        <div>
                            <p class="font-semibold" x-text="laeufer.name"></p>
                            <p class="text-sm text-slate-600" x-text="laeufer.startnummer ? 'Startnummer ' + laeufer.startnummer : 'noch keine Startnummer'"></p>
                        </div>
                        <input type="hidden" name="laeufer_id" :value="laeufer.id">
                        <input type="hidden" name="laeufer_name" :value="laeufer.name">
                        <button type="button" class="btn-light btn-sm" @click="laeufer = null">ändern</button>
                    </div>
                </template>

                <div x-show="!laeufer && !neu" x-data="suche(@js(route('api.laeufer')))" class="relative">
                    <label for="laeufer-suche">Angemeldeten Läufer suchen</label>
                    <input id="laeufer-suche" type="search" x-model="q" @input.debounce.250ms="suchen()" placeholder="Name oder Startnummer" autocomplete="off" autofocus>
                    <ul x-show="offen && treffer.length" x-cloak class="absolute z-10 mt-1 max-h-72 w-full overflow-auto rounded-lg bg-white shadow-lg ring-1 ring-slate-200">
                        <template x-for="t in treffer" :key="t.id">
                            <li><button type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-esz-50" @click="$dispatch('laeufer-gewaehlt', t); q = ''; offen = false">
                                <span class="font-medium" x-text="t.name"></span>
                                <span class="text-slate-500" x-text="(t.startnummer ? ' · #' + t.startnummer : '') + (t.klasse ? ' · ' + t.klasse : '') + (t.team ? ' · Team ' + t.team : '')"></span>
                            </button></li>
                        </template>
                    </ul>
                    <p x-show="offen && !treffer.length && !laden" x-cloak class="hint">Kein Läufer gefunden.</p>
                    <button type="button" class="btn-secondary mt-4 w-full" @click="$dispatch('neuer-laeufer')"><x-icon name="plus" class="size-4" /> Neuen Läufer erfassen</button>
                </div>

                <div x-show="neu && !laeufer" x-cloak class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-input name="vorname" label="Vorname" x-bind:required="neu && !laeufer" />
                        <x-input name="nachname" label="Nachname" x-bind:required="neu && !laeufer" />
                        <x-input name="geburtsdatum" type="date" label="Geburtsdatum" />
                        <x-select name="geschlecht" label="Geschlecht" :options="\App\Services\Statistik::GESCHLECHTER" placeholder="–" />
                    </div>
                    <x-select name="kategorie" label="Kategorie" :options="\App\Enums\Kategorie::options()" placeholder="bitte wählen" x-model="kategorie" x-bind:required="neu && !laeufer" />
                    <div x-show="kategorie === 'schueler'">
                        <x-select name="schulklasse_id" label="Klasse" :options="$klassen" placeholder="bitte wählen" />
                    </div>
                    <button type="button" class="text-sm text-slate-500 underline" @click="neu = false">zurück zur Suche</button>
                </div>
            </x-card>

            <x-card title="2. Startnummer & Team">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="startnummer">Startnummer</label>
                        <input id="startnummer" type="number" name="startnummer" min="1" x-model="startnummer" @input.debounce.300ms="pruefen()" :placeholder="'automatisch: ' + naechste" value="{{ old('startnummer') }}">
                        <p class="mt-1 text-sm" x-show="pruefung" x-cloak :class="pruefung?.frei ? 'text-emerald-700' : 'text-red-600'"
                           x-text="pruefung ? (pruefung.frei ? 'frei' : (pruefung.laeufer ? 'vergeben an ' + pruefung.laeufer.name : 'nicht verfügbar')) : ''"></p>
                        @error('startnummer')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-select name="team_id" label="Team" :options="$teams" placeholder="kein / unverändert" />
                        <x-input name="teamname" label="oder neues Team" class="mt-2" />
                    </div>
                </div>
            </x-card>
        </div>

        {{-- Sponsoren --}}
        <div class="space-y-4 xl:col-span-3">
            <div class="flex items-center justify-between">
                <h2 class="text-lg">3. Sponsoren & Spenden</h2>
                <button type="button" class="btn-secondary btn-sm" @click="sponsorHinzufuegen()"><x-icon name="plus" class="size-4" /> Sponsor</button>
            </div>
            <p x-show="!sponsoren.length" class="rounded-lg border-2 border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                Optional – Spenden können auch später eingetragen werden.
            </p>

            <template x-for="(s, i) in sponsoren" :key="i">
                <div class="card p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold" x-text="'Sponsor ' + (i + 1)"></p>
                        <button type="button" class="text-sm text-red-600" @click="sponsoren.splice(i, 1)">entfernen</button>
                    </div>

                    <template x-if="s.sponsor_id">
                        <div class="mb-3 flex items-center justify-between rounded-lg bg-navy-50 p-2 text-sm">
                            <span x-text="s.sponsor_name"></span>
                            <input type="hidden" :name="`sponsoren[${i}][sponsor_id]`" :value="s.sponsor_id">
                            <input type="hidden" :name="`sponsoren[${i}][sponsor_name]`" :value="s.sponsor_name">
                            <button type="button" class="underline" @click="s.sponsor_id = ''">anderer Sponsor</button>
                        </div>
                    </template>

                    <div x-show="!s.sponsor_id" class="space-y-3">
                        <div x-data="suche(@js(route('api.sponsoren')))" class="relative">
                            <input type="search" x-model="q" @input.debounce.250ms="suchen()" placeholder="vorhandenen Sponsor suchen …" autocomplete="off" aria-label="Sponsor suchen">
                            <ul x-show="offen && treffer.length" x-cloak class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white shadow-lg ring-1 ring-slate-200">
                                <template x-for="t in treffer" :key="t.id">
                                    <li><button type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-esz-50" @click="s.sponsor_id = t.id; s.sponsor_name = t.name; offen = false; q = ''">
                                        <span class="font-medium" x-text="t.name"></span> <span class="text-slate-500" x-text="[t.ort, t.email].filter(Boolean).join(' · ')"></span>
                                    </button></li>
                                </template>
                            </ul>
                        </div>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <template x-for="a in ['Frau', 'Herr', 'Firma']">
                                <label class="flex items-center gap-1 font-normal"><input type="radio" :name="`sponsoren[${i}][anrede]`" :value="a" x-model="s.anrede"> <span x-text="a"></span></label>
                            </template>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input x-show="s.anrede === 'Firma'" type="text" :name="`sponsoren[${i}][firmenname]`" x-model="s.firmenname" placeholder="Firma" class="sm:col-span-2">
                            <input type="text" :name="`sponsoren[${i}][vorname]`" x-model="s.vorname" placeholder="Vorname" :required="!s.sponsor_id && s.anrede !== 'Firma'">
                            <input type="text" :name="`sponsoren[${i}][nachname]`" x-model="s.nachname" placeholder="Nachname" :required="!s.sponsor_id && s.anrede !== 'Firma'">
                            <input type="email" :name="`sponsoren[${i}][email]`" x-model="s.email" placeholder="E-Mail (für Zahlungsinfo)">
                            <input type="tel" :name="`sponsoren[${i}][telefon]`" x-model="s.telefon" placeholder="Telefon">
                            <input type="text" :name="`sponsoren[${i}][strasse]`" x-model="s.strasse" placeholder="Straße und Hausnummer *" class="sm:col-span-2" :required="!s.sponsor_id">
                            <input type="text" :name="`sponsoren[${i}][plz]`" x-model="s.plz" placeholder="PLZ *" :required="!s.sponsor_id">
                            <input type="text" :name="`sponsoren[${i}][ort]`" x-model="s.ort" placeholder="Ort *" :required="!s.sponsor_id">
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-4">
                        <div><label>je Runde (€)</label><input type="number" step="0.01" min="0" :name="`sponsoren[${i}][rundenBetrag]`" x-model="s.rundenBetrag"></div>
                        <div><label>Festbetrag (€)</label><input type="number" step="0.01" min="0" :name="`sponsoren[${i}][festBetrag]`" x-model="s.festBetrag"></div>
                        <div><label>max. (€)</label><input type="number" step="0.01" min="0" :name="`sponsoren[${i}][maxBetrag]`" x-model="s.maxBetrag"></div>
                        <div><label>bar erhalten (€)</label><input type="number" step="0.01" min="0" :name="`sponsoren[${i}][bar]`" x-model="s.bar"></div>
                    </div>
                    <div class="mt-3 flex gap-4 text-sm">
                        <label class="flex items-center gap-1 font-normal"><input type="radio" :name="`sponsoren[${i}][ziel]`" value="laeufer" x-model="s.ziel"> für den Läufer</label>
                        <label class="flex items-center gap-1 font-normal"><input type="radio" :name="`sponsoren[${i}][ziel]`" value="team" x-model="s.ziel"> für sein Team</label>
                    </div>
                </div>
            </template>

            <button type="submit" class="btn-primary btn-lg w-full">Speichern & nächster Läufer</button>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('schnellerfassung', (start) => ({
                    laeufer: start.laeufer,
                    neu: start.neu,
                    kategorie: start.kategorie,
                    naechste: start.naechste,
                    sponsoren: start.sponsoren,
                    startnummer: '',
                    pruefung: null,
                    init() {
                        this.$el.addEventListener('laeufer-gewaehlt', (e) => { this.laeufer = e.detail; this.neu = false; });
                        this.$el.addEventListener('neuer-laeufer', () => { this.neu = true; });
                    },
                    sponsorHinzufuegen() {
                        this.sponsoren.push({ sponsor_id: '', sponsor_name: '', anrede: 'Frau', vorname: '', nachname: '', firmenname: '', email: '', telefon: '',
                            strasse: '', plz: '', ort: '', festBetrag: '', rundenBetrag: '', maxBetrag: '', ziel: 'laeufer', bar: '' });
                    },
                    async pruefen() {
                        if (!this.startnummer) { this.pruefung = null; return; }
                        try { this.pruefung = await window.api(@js(url('intern/api/startnummer')) + '/' + this.startnummer); } catch (e) { this.pruefung = null; }
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
