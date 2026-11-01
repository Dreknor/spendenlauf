@php
    use Illuminate\Support\Carbon;
    $datum = fn (?string $wert) => $wert ? Carbon::parse($wert)->timezone(config('app.timezone'))->format('d.m.Y H:i') : '–';
    $laeuft = \App\Enums\UpdateZustand::tryFrom($status['zustand'] ?? '')?->aktiv() ?? false;
@endphp
<x-layouts.app title="System-Update">
    <x-page-header title="System-Update" subtitle="Neue Versionen des Spendenlaufs aus dem git-Repository einspielen – mit denselben Schritten wie deploy.sh.">
        <form method="POST" action="{{ route('updates.pruefen') }}">
            @csrf
            <button type="submit" class="btn-light" @disabled($laeuft)><x-icon name="refresh" class="size-4" /> Jetzt prüfen</button>
        </form>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Verfügbare Updates" :subtitle="$pruefung ? 'Zuletzt geprüft am '.$datum($pruefung['geprueft_am']).' · '.$pruefung['ziel'] : 'Noch nicht geprüft'">
                @if (! $pruefung)
                    <x-empty message="Klicken Sie auf „Jetzt prüfen“, um nach einer neuen Version zu suchen." />
                @elseif ($pruefung['fehler'])
                    <div class="flex gap-3 rounded-lg bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
                        <x-icon name="warning" class="size-5 shrink-0" />
                        <p class="break-words">{{ $pruefung['fehler'] }}</p>
                    </div>
                @else
                    @if ($pruefung['aenderungen'])
                        <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                            <p class="font-semibold">Auf dem Server wurden Dateien geändert – ein Update ist erst nach dem Committen oder Verwerfen möglich:</p>
                            <ul class="mt-1 list-disc pl-5 font-mono text-xs">
                                @foreach (array_slice($pruefung['aenderungen'], 0, 15) as $datei)
                                    <li>{{ $datei }}</li>
                                @endforeach
                                @if (count($pruefung['aenderungen']) > 15)<li>… und {{ count($pruefung['aenderungen']) - 15 }} weitere</li>@endif
                            </ul>
                        </div>
                    @endif
                    @if ($pruefung['lokal_voraus'] > 0)
                        <div class="mb-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                            Die Installation enthält {{ $pruefung['lokal_voraus'] }} Commit(s), die in <code>{{ $pruefung['ziel'] }}</code> fehlen. Ein automatisches Update ist nicht möglich.
                        </div>
                    @endif

                    @if ($pruefung['neu'] > 0)
                        <p class="text-sm">
                            <x-badge color="esz">{{ $pruefung['neu'] }} neu</x-badge>
                            Folgende Änderungen werden installiert:
                        </p>
                        <ul class="mt-3 divide-y divide-slate-100 text-sm">
                            @foreach ($pruefung['commits'] as $commit)
                                <li class="flex gap-3 py-2">
                                    <code class="shrink-0 pt-0.5 text-xs text-slate-500">{{ $commit['kurz'] }}</code>
                                    <div class="min-w-0">
                                        <p class="font-medium break-words">{{ $commit['betreff'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $commit['autor'] }} · {{ $datum($commit['datum']) }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if ($pruefung['neu'] > count($pruefung['commits']))
                            <p class="hint">… und {{ $pruefung['neu'] - count($pruefung['commits']) }} ältere Änderungen.</p>
                        @endif

                        @if (! $pruefung['aenderungen'] && ! $pruefung['lokal_voraus'])
                            <form method="POST" action="{{ route('updates.starten') }}" class="mt-4"
                                  onsubmit="return confirm('Update jetzt installieren? Die Seite ist dabei für einige Minuten im Wartungsmodus.')">
                                @csrf
                                <button type="submit" class="btn-primary" @disabled($laeuft)><x-icon name="download" class="size-4" /> Update jetzt installieren</button>
                            </form>
                        @endif
                    @else
                        <p class="flex items-center gap-2 text-sm text-emerald-700"><x-icon name="check" class="size-5" /> Die installierte Version ist aktuell.</p>
                    @endif
                @endif
            </x-card>

            <div x-data="updateFortschritt(@js($status), @js(route('updates.status')))">
                <x-card title="Update-Verlauf">
                    <x-slot:actions>
                        <template x-if="status.zustand">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                  :class="farbe()" x-text="label()"></span>
                        </template>
                    </x-slot:actions>

                    <template x-if="!status.zustand">
                        <x-empty message="Es wurde noch kein Update über die Verwaltung ausgeführt." />
                    </template>

                    <template x-if="status.zustand">
                        <div class="space-y-4">
                            <dl class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                                <div><dt class="inline text-slate-500">Gestartet:</dt> <dd class="inline" x-text="zeit(status.gestartet_am) + (status.gestartet_von ? ' von ' + status.gestartet_von : '')"></dd></div>
                                <div x-show="status.beendet_am"><dt class="inline text-slate-500">Beendet:</dt> <dd class="inline" x-text="zeit(status.beendet_am)"></dd></div>
                                <div x-show="status.von"><dt class="inline text-slate-500">Version:</dt> <dd class="inline font-mono text-xs" x-text="status.von + (status.nach && status.nach !== status.von ? ' → ' + status.nach : '')"></dd></div>
                            </dl>

                            <p x-show="status.meldung" class="rounded-lg p-3 text-sm ring-1"
                               :class="['fehlgeschlagen', 'abgebrochen'].includes(status.zustand) ? 'bg-red-50 text-red-800 ring-red-200' : 'bg-emerald-50 text-emerald-800 ring-emerald-200'"
                               x-text="status.meldung"></p>

                            <ol class="space-y-1 text-sm">
                                <template x-for="(schritt, i) in status.schritte || []" :key="i">
                                    <li class="flex items-start gap-2">
                                        <span class="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700': schritt.zustand === 'ok',
                                                  'bg-red-100 text-red-700': schritt.zustand === 'fehler',
                                                  'bg-navy-100 text-navy-700 animate-pulse': schritt.zustand === 'laeuft',
                                                  'bg-slate-100 text-slate-500': schritt.zustand === 'uebersprungen',
                                              }"
                                              x-text="{ ok: '✓', fehler: '✕', laeuft: '…', uebersprungen: '–' }[schritt.zustand]"></span>
                                        <span class="flex-1">
                                            <span x-text="schritt.name"></span>
                                            <span x-show="schritt.dauer !== null" class="text-xs text-slate-400" x-text="'(' + String(schritt.dauer).replace('.', ',') + ' s)'"></span>
                                            <span x-show="schritt.hinweis" class="block text-xs text-slate-500" x-text="schritt.hinweis"></span>
                                        </span>
                                    </li>
                                </template>
                            </ol>

                            <details :open="aktiv()">
                                <summary class="cursor-pointer text-sm font-medium text-slate-600">Ausgabe</summary>
                                <pre x-ref="ausgabe" class="mt-2 max-h-96 overflow-auto rounded-lg bg-slate-900 p-3 font-mono text-xs leading-relaxed whitespace-pre-wrap text-slate-100" x-text="status.ausgabe || '–'"></pre>
                            </details>

                            <p x-show="verbindungsfehler" class="text-xs text-amber-700">Keine Verbindung zum Server – während der Installation ist das normal, es wird weiter versucht …</p>
                            <p x-show="fertigGeworden" class="text-sm">
                                <a href="{{ route('updates.index') }}" class="btn-light btn-sm">Seite neu laden</a>
                            </p>
                        </div>
                    </template>
                </x-card>
            </div>
        </div>

        <div class="space-y-6">
            <x-card title="Installierte Version">
                @if ($version['verfuegbar'])
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-slate-500">Version</dt><dd class="font-mono">{{ $version['bezeichnung'] }}</dd></div>
                        <div><dt class="text-slate-500">Branch</dt><dd class="font-mono">{{ $version['branch'] }}</dd></div>
                        <div><dt class="text-slate-500">Stand</dt><dd>{{ $datum($version['datum']) }}</dd></div>
                        <div><dt class="text-slate-500">Letzte Änderung</dt><dd class="break-words">{{ $version['betreff'] }}</dd></div>
                    </dl>
                @else
                    <p class="text-sm text-red-700">Die Version konnte nicht ermittelt werden: {{ $version['fehler'] }}</p>
                @endif
            </x-card>

            @if ($pruefung['werkzeuge'] ?? null)
                <x-card title="Server-Umgebung" subtitle="Programme, die der Updater verwendet">
                    <dl class="space-y-2 text-sm">
                        @foreach ($pruefung['werkzeuge'] as $name => $werkzeug)
                            <div>
                                <dt class="flex items-center gap-1 text-slate-500">
                                    @if ($werkzeug['ok'] === true)<x-icon name="check" class="size-4 text-emerald-600" />
                                    @elseif ($werkzeug['ok'] === false)<x-icon name="warning" class="size-4 text-red-600" />
                                    @else<x-icon name="info" class="size-4 text-slate-400" />@endif
                                    {{ $name }}
                                </dt>
                                <dd @class(['break-words', 'text-red-700' => $werkzeug['ok'] === false])>{{ $werkzeug['version'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>
            @endif

            <x-card title="So funktioniert's">
                <ul class="list-disc space-y-1.5 pl-5 text-sm text-slate-600">
                    <li>Das Update läuft im Hintergrund weiter, auch wenn Sie diese Seite schließen.</li>
                    <li>Während der Installation ist die Seite für alle anderen im Wartungsmodus (meist 1–3 Minuten).</li>
                    <li>Schlägt ein Schritt fehl, wird die vorherige Version automatisch wiederhergestellt. Datenbank-Änderungen bleiben bestehen – vor größeren Updates daher ein Backup anlegen.</li>
                    <li>Das vollständige Protokoll steht auf dem Server in <code>storage/app/updater/update.log</code>.</li>
                </ul>
                @if ($pruefung && ! $pruefung['fehler'] && $pruefung['neu'] === 0 && ! $pruefung['aenderungen'] && ! $pruefung['lokal_voraus'])
                    <form method="POST" action="{{ route('updates.starten') }}" class="mt-4"
                          onsubmit="return confirm('Installationsschritte (Composer, Frontend, Migrationen, Caches) erneut ausführen?')">
                        @csrf
                        <input type="hidden" name="erzwingen" value="1">
                        <button type="submit" class="btn-light btn-sm" @disabled($laeuft)>Installation erneut ausführen</button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('updateFortschritt', (start, url) => ({
                    status: start || {},
                    verbindungsfehler: false,
                    fertigGeworden: false,
                    timer: null,
                    init() {
                        if (this.aktiv()) this.abfragen();
                    },
                    aktiv() {
                        return ['gestartet', 'laeuft'].includes(this.status.zustand);
                    },
                    async abfragen() {
                        clearTimeout(this.timer);
                        try {
                            this.status = await window.api(url);
                            this.verbindungsfehler = false;
                            this.$nextTick(() => { if (this.$refs.ausgabe) this.$refs.ausgabe.scrollTop = this.$refs.ausgabe.scrollHeight; });
                        } catch (e) {
                            this.verbindungsfehler = true;
                        }
                        if (this.aktiv() || this.verbindungsfehler) {
                            this.timer = setTimeout(() => this.abfragen(), 2000);
                        } else {
                            this.fertigGeworden = true;
                        }
                    },
                    label() {
                        return {
                            gestartet: 'wird gestartet', laeuft: 'läuft', erfolgreich: 'erfolgreich', aktuell: 'bereits aktuell',
                            fehlgeschlagen: 'fehlgeschlagen', abgebrochen: 'abgebrochen',
                        }[this.status.zustand] || this.status.zustand;
                    },
                    farbe() {
                        if (this.aktiv()) return 'bg-navy-50 text-navy-700 ring-navy-200';
                        return ['erfolgreich', 'aktuell'].includes(this.status.zustand)
                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                            : 'bg-red-50 text-red-700 ring-red-200';
                    },
                    zeit(wert) {
                        return wert ? new Date(wert).toLocaleString('de-DE', { dateStyle: 'medium', timeStyle: 'short' }) : '–';
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
