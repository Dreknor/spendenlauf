{{--
    Hinweis auf bereits angemeldete Läufer (aus session('dubletten')).
    $modus: 'anmeldung' (mit Button „Mitverwaltung anfragen“, außerhalb des Formulars)
            'schnellerfassung' (Button „diesen Läufer verwenden“, innerhalb des Alpine-Formulars)
--}}
@php
    $treffer = collect(session('dubletten', []));
    $laeuferListe = \App\Models\Laeufer::with(['besitzer', 'schulklasse', 'team'])->findMany($treffer->pluck('id'))->keyBy('id');
    $eigeneAnfragen = \App\Models\MitverwaltungAnfrage::where('user_id', auth()->id())
        ->where('status', \App\Models\MitverwaltungAnfrage::OFFEN)->pluck('laeufer_id')->all();
@endphp
@if ($treffer->isNotEmpty())
    <div class="mb-6 rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 sm:p-5" role="alert">
        <p class="font-semibold text-amber-900">
            @if ($treffer->contains('grad', 'sicher'))
                Dieser Läufer ist bereits angemeldet.
            @else
                Es gibt bereits einen ähnlichen Läufer. Ist das dieselbe Person?
            @endif
        </p>
        <ul class="mt-3 space-y-2">
            @foreach ($treffer as $t)
                @php $l = $laeuferListe->get($t['id']); @endphp
                @continue(! $l)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-3 text-sm ring-1 ring-amber-100">
                    <div>
                        <span class="font-semibold">{{ $l->name }}</span>
                        @if ($l->geburtsdatum) · geb. {{ $l->geburtsdatum->format('Y') }}@endif
                        @if ($l->schulklasse) · Klasse {{ $l->schulklasse->name }}@endif
                        @if ($l->team) · Team {{ $l->team->name }}@endif
                        <x-badge :color="$t['grad'] === 'sicher' ? 'red' : 'amber'">{{ $t['grad'] === 'sicher' ? 'gleich' : 'ähnlich' }}</x-badge>
                        <p class="text-xs text-slate-500">
                            @if ($l->istVerwaltetVon(auth()->user()))
                                von Ihnen verwaltet
                            @elseif (auth()->user()->can('edit laeufer'))
                                angemeldet von {{ $l->besitzer?->name ?? '–' }} · Startnummer {{ $l->startnummer }}
                            @else
                                von einem anderen Konto angemeldet (z. B. dem anderen Elternteil)
                            @endif
                        </p>
                    </div>
                    @if ($modus === 'schnellerfassung')
                        <button type="button" class="btn-secondary btn-sm"
                                @click="$dispatch('laeufer-gewaehlt', @js(['id' => $l->id, 'name' => $l->name, 'startnummer' => $l->startnummer]))">
                            Diesen Läufer verwenden
                        </button>
                    @elseif ($l->istVerwaltetVon(auth()->user()))
                        <a href="{{ route('laeufer.edit', $l) }}" class="btn-light btn-sm">Zum Läufer</a>
                    @elseif (in_array($l->id, $eigeneAnfragen))
                        <x-badge color="blue">Mitverwaltung angefragt</x-badge>
                    @else
                        <form method="POST" action="{{ route('mitverwaltung.anfragen', $l) }}">
                            @csrf
                            <button type="submit" class="btn-secondary btn-sm">Mitverwaltung anfragen</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($modus === 'anmeldung')
            <p class="mt-3 text-xs text-amber-900">
                Mit der Mitverwaltung sehen und bearbeiten Sie den bereits angemeldeten Läufer gemeinsam – der Elternteil, der ihn angemeldet hat, muss die Anfrage bestätigen.
                @unless (session('dubletten_uebergehbar'))
                    Eine zweite Anmeldung derselben Person ist nicht möglich.
                @endunless
            </p>
        @endif
    </div>
@endif
