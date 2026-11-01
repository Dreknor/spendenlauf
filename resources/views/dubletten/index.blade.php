@php $istLaeufer = $art === 'laeufer'; @endphp
<x-layouts.app title="Doppelte Einträge">
    <x-page-header title="Doppelte Einträge" subtitle="Mögliche Dubletten prüfen und zusammenführen. Spenden, Zahlungen und Zuordnungen werden dabei übernommen, der zweite Eintrag wird gelöscht." />

    <div class="mb-6 flex gap-2">
        @can('edit laeufer')
            <a href="{{ route('dubletten.index', ['art' => 'laeufer']) }}" @class(['btn-sm', 'btn-secondary' => $istLaeufer, 'btn-light' => ! $istLaeufer])>Läufer (aktueller Lauf)</a>
        @endcan
        @can('edit sponsoren')
            <a href="{{ route('dubletten.index', ['art' => 'sponsoren']) }}" @class(['btn-sm', 'btn-secondary' => ! $istLaeufer, 'btn-light' => $istLaeufer])>Sponsoren</a>
        @endcan
    </div>

    @if ($paare->isEmpty())
        <x-empty message="Keine möglichen Dubletten gefunden." />
    @else
        <p class="mb-4 text-sm text-slate-600">{{ $paare->count() }} mögliche Dubletten. Wählen Sie, welcher Eintrag erhalten bleiben soll.</p>
        <div class="space-y-4">
            @foreach ($paare as $p)
                <div class="card p-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <x-badge :color="$p['grad'] === 'sicher' ? 'red' : 'amber'">{{ $p['grad'] === 'sicher' ? 'sehr wahrscheinlich gleich' : 'ähnlich' }}</x-badge>
                        <form method="POST" action="{{ route('dubletten.ignorieren') }}">
                            @csrf
                            <input type="hidden" name="typ" value="{{ $istLaeufer ? 'laeufer' : 'sponsor' }}">
                            <input type="hidden" name="a" value="{{ $p['a']->id }}">
                            <input type="hidden" name="b" value="{{ $p['b']->id }}">
                            <button class="text-sm text-slate-500 underline hover:text-slate-700">keine Dublette</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ $istLaeufer ? route('dubletten.laeufer') : route('dubletten.sponsoren') }}"
                          x-data="{ behalten: {{ $p['a']->id }} }" onsubmit="return confirm('Einträge wirklich zusammenführen? Das lässt sich nicht rückgängig machen.')">
                        @csrf
                        <input type="hidden" name="behalten" :value="behalten">
                        <input type="hidden" name="entfernen" :value="behalten === {{ $p['a']->id }} ? {{ $p['b']->id }} : {{ $p['a']->id }}">
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach ([$p['a'], $p['b']] as $e)
                                <label class="flex cursor-pointer gap-3 rounded-lg p-3 font-normal ring-1 ring-slate-200 has-checked:bg-esz-50 has-checked:ring-esz-400">
                                    <input type="radio" class="mt-1" x-model.number="behalten" value="{{ $e->id }}">
                                    <span class="text-sm">
                                        @if ($istLaeufer)
                                            <strong>{{ $e->name }}</strong> · Startnummer {{ $e->startnummer ?? '–' }}<br>
                                            geb. {{ $e->geburtsdatum?->format('d.m.Y') ?? '–' }} · {{ $e->kategorie?->label() ?? '–' }}{{ $e->schulklasse ? ' · '.$e->schulklasse->name : '' }}{{ $e->team ? ' · Team '.$e->team->name : '' }}<br>
                                            <span class="text-slate-500">angemeldet von {{ $e->besitzer?->name ?? '–' }} am {{ $e->created_at?->format('d.m.Y') }} · {{ $e->runden }} Runden · {{ $e->sponsorings_count }} Spenden</span>
                                        @else
                                            <strong>{{ $e->name }}</strong> <span class="text-slate-500">Nr. {{ $e->id }}</span><br>
                                            {{ $e->strasse }}, {{ $e->plz }} {{ $e->ort }}<br>
                                            <span class="text-slate-500">{{ $e->email ?? 'keine E-Mail' }} · {{ $e->sponsorings_count }} Spenden (alle Läufe)</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($istLaeufer && $p['a']->startnummer && $p['b']->startnummer)
                            @php $vorschlag = \App\Actions\Zusammenfuehren::nummerQuelle($p['a'], $p['b']); @endphp
                            <fieldset class="mt-3 rounded-lg bg-slate-50 p-3 text-sm">
                                <legend class="font-medium text-slate-700">Startnummer und Runden übernehmen von</legend>
                                <div class="mt-1 flex flex-wrap gap-4">
                                    @foreach ([$p['a'], $p['b']] as $e)
                                        <label class="flex items-center gap-2 font-normal">
                                            <input type="radio" name="startnummer" value="{{ $e->startnummer }}" @checked($e->is($vorschlag))>
                                            Nr. {{ $e->startnummer }} ({{ $e->runden }} Runden)
                                        </label>
                                    @endforeach
                                </div>
                                <p class="hint">Die Zeitmessung zählt pro Startnummer – behalten Sie die Nummer, die der Läufer trägt. Die andere Nummer wird wieder frei, sofern darauf noch keine Runden gezählt wurden.</p>
                            </fieldset>
                        @endif
                        <button type="submit" class="btn-primary btn-sm mt-3">Ausgewählten behalten und zusammenführen</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
