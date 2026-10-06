<x-layouts.public :title="'Sponsor für '.$laeufer->vorname">
    <div class="grid gap-8 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <p class="text-sm font-semibold uppercase tracking-wider text-esz-600">Werden Sie Sponsor!</p>
            <h1 class="mt-1 text-3xl">{{ $laeufer->vorname }} {{ mb_substr($laeufer->nachname, 0, 1) }}. läuft für den guten Zweck</h1>
            <p class="mt-3 text-slate-600">
                Beim {{ $event->name }} am {{ $event->datum->translatedFormat('l, d. F Y') }} laufen Schülerinnen und Schüler, Eltern und Gäste
                {{ $event->dauer_minuten >= 60 && $event->dauer_minuten % 60 === 0 ? ($event->dauer_minuten / 60).' Stunden' : $event->dauer_minuten.' Minuten' }} lang
                Runden zu je {{ $event->runden_laenge_m }} m. Sie entscheiden, ob Sie jede Runde oder mit einem festen Betrag unterstützen.
            </p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <x-stat label="Sponsoren bisher" :value="$laeufer->sponsorings->count()" />
                @if ($event->laufVorbei() || $laeufer->runden > 0)
                    <x-stat label="Runden" :value="$laeufer->runden" />
                    <x-stat label="Erlaufen" :value="euro($spende)" accent class="col-span-2" />
                @endif
            </div>
            @if ($laeufer->team)
                <p class="mt-4 text-sm text-slate-600">{{ $laeufer->vorname }} läuft im Team <strong>{{ $laeufer->team->name }}</strong>.</p>
            @endif

            <h2 class="mt-8 text-lg">Diese Projekte werden unterstützt</h2>
            <div class="mt-3 space-y-2">
                @foreach ($projekte as $p)
                    <details class="card group p-4">
                        <summary class="flex cursor-pointer items-center justify-between font-medium text-navy-800">{{ $p->name }} <span class="text-esz-600 group-open:rotate-45">+</span></summary>
                        @if ($url = $p->bildUrl())<img src="{{ $url }}" alt="" class="mt-3 h-40 w-full rounded-lg object-cover" loading="lazy">@endif
                        <div class="mt-2 space-y-2 text-sm text-slate-600">{!! $p->beschreibungHtml() !!}</div>
                    </details>
                @endforeach
            </div>
            <div class="mt-4">@include('partials.verteilung-hinweis', ['event' => $event])</div>
        </div>

        <div class="lg:col-span-3">
            @if ($event->anmeldungOffen())
                <x-card title="Ihre Spendenzusage">
                    <form method="POST" action="{{ route('public.sponsor.store', $laeufer->uuid) }}" class="space-y-5"
                          x-data="{ fest: @js(old('festBetrag', '')), runde: @js(old('rundenBetrag', '')), max: @js(old('maxBetrag', '')),
                                    get beispiel() { let s = (parseFloat(this.fest) || 0) + (parseFloat(this.runde) || 0) * 20; const m = parseFloat(this.max) || 0; return m > 0 && s > m ? m : s; } }">
                        @csrf
                        <x-honeypot />
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-input name="rundenBetrag" type="number" step="0.01" min="0" label="je Runde (€)" x-model="runde" placeholder="z. B. 1,00" />
                            <x-input name="festBetrag" type="number" step="0.01" min="0" label="und/oder fest (€)" x-model="fest" />
                            <x-input name="maxBetrag" type="number" step="0.01" min="0" label="höchstens (€)" x-model="max" hint="optional" />
                        </div>
                        <p class="rounded-lg bg-navy-50 p-3 text-sm text-navy-800" x-show="beispiel > 0" x-cloak>
                            Bei 20 Runden wären das <strong x-text="window.euro(Math.round(beispiel * 100))"></strong>.
                        </p>

                        @if ($projekte->count() > 1)
                            <fieldset>
                                <legend class="text-sm font-medium text-slate-700">Bestimmte Projekte unterstützen? (optional)</legend>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ($projekte as $p)
                                        <x-checkbox name="projects[]" :value="(string) $p->id" :label="$p->name" />
                                    @endforeach
                                </div>
                                <p class="hint">Ohne Auswahl wird gleichmäßig auf alle Projekte verteilt.</p>
                            </fieldset>
                        @endif

                        <hr class="border-slate-100">
                        @include('sponsoren._form', ['sponsor' => null])
                        <x-checkbox name="datenschutz">
                            <x-slot:label>Ich bin mit der Verarbeitung meiner Daten zur Abwicklung des Spendenlaufs gemäß <a href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutzerklärung</a> einverstanden.</x-slot:label>
                        </x-checkbox>
                        <p class="text-xs text-slate-500">Nach dem Lauf erhalten Sie eine E-Mail mit dem erlaufenen Betrag und den Zahlungsinformationen. Sie zahlen erst dann.</p>
                        <button type="submit" class="btn-primary btn-lg w-full">Spende zusagen</button>
                    </form>
                </x-card>
            @else
                <x-card>
                    <p class="text-slate-600">Für diesen Spendenlauf können keine neuen Spenden mehr zugesagt werden. Vielen Dank für Ihr Interesse!</p>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.public>
