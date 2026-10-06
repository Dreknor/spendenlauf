<x-layouts.app title="Runden eingeben">
    <x-page-header title="Runden eingeben" :subtitle="'Bisher insgesamt '.zahl($summe).' Runden · '.zahl($event->km($summe), 1).' km'" />

    <div class="grid gap-6 lg:grid-cols-2" x-data="rundenEingabe()">
        <x-card title="Startnummer & Runden">
            <form @submit.prevent="speichern()" method="POST" action="{{ route('runden.update') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="startnummer">Startnummer</label>
                    <input id="startnummer" x-ref="nummer" type="number" name="startnummer" min="1" required inputmode="numeric" autofocus
                           x-model="startnummer" @input.debounce.250ms="nachschlagen()" class="text-2xl! font-bold tabular-nums">
                    <p class="mt-2 min-h-6 text-sm" :class="laeufer ? 'text-navy-800' : 'text-red-600'"
                       x-text="laeufer ? laeufer.name + (laeufer.klasse ? ' · ' + laeufer.klasse : '') + ' – bisher ' + laeufer.runden + ' Runden' + (laeufer.runden_manuell ? ' (manuell)' : '') : (startnummer && geprueft ? 'Startnummer nicht vergeben' : '')"></p>
                </div>
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2 font-normal"><input type="radio" name="modus" value="setzen" x-model="modus"> Runden setzen</label>
                    <label class="flex items-center gap-2 font-normal"><input type="radio" name="modus" value="addieren" x-model="modus"> Runden hinzufügen</label>
                </div>
                <div>
                    <label for="runden" x-text="modus === 'setzen' ? 'Anzahl Runden (gesamt)' : 'Runden hinzufügen'">Runden</label>
                    <input id="runden" x-ref="runden" type="number" name="runden" required inputmode="numeric" x-model="runden" class="text-2xl! font-bold tabular-nums">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary btn-lg flex-1" :disabled="laden">Speichern</button>
                    <button type="button" class="btn-secondary btn-lg" :disabled="!laeufer || laden" @click="modus = 'addieren'; runden = 1; speichern()">+1 Runde</button>
                </div>
                <p x-show="fehler" x-cloak class="field-error" x-text="fehler"></p>
            </form>
            <p class="hint mt-4">Manuell eingegebene Runden werden vom automatischen Import der Zeitmessung nicht überschrieben.</p>
        </x-card>

        <x-card title="Zuletzt geändert" :padding="false">
            <ul class="divide-y divide-slate-100 text-sm">
                <template x-for="e in verlauf" :key="e.zeit">
                    <li class="flex justify-between px-4 py-2 bg-emerald-50">
                        <span x-text="'#' + e.startnummer + ' ' + e.name"></span>
                        <span class="tabular-nums" x-text="e.vorher + ' → ' + e.runden"></span>
                    </li>
                </template>
                @foreach ($zuletzt as $l)
                    <li class="flex justify-between px-4 py-2">
                        <span>#{{ $l->startnummer }} {{ $l->name }}</span>
                        <span class="tabular-nums">{{ $l->runden }} <span class="text-xs text-slate-400">{{ $l->runden_aktualisiert_am->format('H:i') }}</span></span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('rundenEingabe', () => ({
                    startnummer: '', runden: '', modus: 'setzen', laeufer: null, geprueft: false, laden: false, fehler: '', verlauf: [],
                    async nachschlagen() {
                        this.geprueft = false; this.laeufer = null;
                        if (!this.startnummer) return;
                        try {
                            const r = await window.api(@js(url('intern/api/startnummer')) + '/' + this.startnummer);
                            this.laeufer = r.laeufer;
                        } finally { this.geprueft = true; }
                    },
                    async speichern() {
                        this.fehler = ''; this.laden = true;
                        try {
                            const r = await window.api(@js(route('runden.update')), {
                                method: 'POST',
                                body: JSON.stringify({ startnummer: this.startnummer, runden: this.runden, modus: this.modus }),
                            });
                            this.verlauf.unshift({ ...r, zeit: Date.now() });
                            this.startnummer = ''; this.runden = ''; this.laeufer = null; this.modus = 'setzen';
                            this.$refs.nummer.focus();
                        } catch (e) {
                            this.fehler = e.message;
                        } finally { this.laden = false; }
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
