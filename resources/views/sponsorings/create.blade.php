<x-layouts.app title="Spende eintragen">
    <x-page-header title="Spende eintragen" subtitle="Ein Sponsor unterstützt einen Läufer oder ein Team." />

    <form method="POST" action="{{ route('sponsorings.store') }}"
          x-data="{ type: @js(old('type', request('team') ? 'team' : 'laeufer')), fest: @js(old('festBetrag', '')), runde: @js(old('rundenBetrag', '')), max: @js(old('maxBetrag', '')), runden: 20,
                    get beispiel() { let s = (parseFloat(this.fest) || 0) + (parseFloat(this.runde) || 0) * this.runden; const m = parseFloat(this.max) || 0; if (m > 0 && s > m) s = m; return s; } }"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        <input type="hidden" name="zurueck" value="{{ request('laeufer') ? route('laeufer.edit', request('laeufer')) : '' }}">

        <div class="space-y-6 lg:col-span-2">
            <x-card title="Wer spendet?">
                @if ($sponsoren->isEmpty())
                    <p class="text-sm text-slate-600">Sie haben noch keine Sponsoren angelegt.</p>
                @else
                    <x-select name="sponsor_id" label="Sponsor" :options="$sponsoren->mapWithKeys(fn ($s) => [$s->id => $s->name.($s->ort ? ' ('.$s->ort.')' : '')])" :selected="request('sponsor')" placeholder="bitte wählen" required />
                @endif
                <a href="{{ route('sponsoren.create', ['weiter' => 'sponsoring', 'laeufer' => request('laeufer')]) }}" class="mt-3 inline-flex items-center gap-1 text-sm"><x-icon name="plus" class="size-4" /> neuen Sponsor anlegen</a>
            </x-card>

            <x-card title="Für wen?">
                <div class="mb-4 flex gap-4 text-sm">
                    <label class="flex items-center gap-2 font-normal"><input type="radio" name="type" value="laeufer" x-model="type"> einzelner Läufer</label>
                    <label class="flex items-center gap-2 font-normal"><input type="radio" name="type" value="team" x-model="type"> Team</label>
                </div>
                <div x-show="type === 'laeufer'">
                    <x-select name="laeufer_id" label="Läufer" :options="$laeufer->mapWithKeys(fn ($l) => [$l->id => $l->nachname.', '.$l->vorname.' (#'.$l->startnummer.')'])" :selected="request('laeufer')" placeholder="bitte wählen" />
                </div>
                <div x-show="type === 'team'" x-cloak>
                    <x-select name="team_id" label="Team" :options="$teams->pluck('name', 'id')" :selected="request('team')" placeholder="bitte wählen" hint="Bei Team-Spenden zählen die Runden aller Teammitglieder." />
                </div>
            </x-card>

            <x-card title="Betrag">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-input name="rundenBetrag" type="number" step="0.01" min="0" label="Betrag je Runde (€)" x-model="runde" />
                    <x-input name="festBetrag" type="number" step="0.01" min="0" label="Festbetrag (€)" x-model="fest" />
                    <x-input name="maxBetrag" type="number" step="0.01" min="0" label="Höchstbetrag (€)" x-model="max" hint="optional" />
                </div>
                <p class="mt-4 rounded-lg bg-navy-50 p-3 text-sm text-navy-800">
                    Beispiel: Bei <input type="number" min="0" x-model.number="runden" class="mx-1 inline-block! w-16! py-0.5! text-sm" aria-label="Runden"> Runden
                    ergibt das <strong x-text="window.euro(Math.round(beispiel * 100))"></strong>.
                </p>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Projekte">
                <p class="mb-3 text-sm text-slate-600">Welche Projekte sollen unterstützt werden? Ohne Auswahl wird gleichmäßig auf alle Projekte verteilt.</p>
                <div class="space-y-2">
                    @foreach ($projekte as $p)
                        <x-checkbox name="projects[]" :value="(string) $p->id" :label="$p->name" />
                    @endforeach
                </div>
                @include('partials.verteilung-hinweis', ['event' => $event])
            </x-card>
            <button type="submit" class="btn-primary btn-lg w-full">Spende speichern</button>
        </div>
    </form>
</x-layouts.app>
