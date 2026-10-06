<x-layouts.app title="Startnummern">
    <x-page-header title="Startnummern" subtitle="Neue Läufer erhalten automatisch die kleinste freie Nummer aus diesem Pool. Ist der Pool leer, wird nach der höchsten vergebenen Nummer weitergezählt." />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Vergeben" :value="zahl($vergebenAnzahl)" />
        <x-stat label="Frei im Pool" :value="zahl($frei->count())" />
        <x-stat label="Höchste vergebene" :value="$hoechste ?? '–'" />
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card title="Nummern hinzufügen">
            <form method="POST" action="{{ route('startnummern.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <x-input name="von" type="number" min="1" label="von" required />
                    <x-input name="bis" type="number" min="1" label="bis" required />
                </div>
                <p class="hint">Z. B. die Nummern der vorgedruckten Startnummern. Bereits vergebene Nummern werden übersprungen.</p>
                <button type="submit" class="btn-primary w-full">Hinzufügen</button>
            </form>
        </x-card>
        <x-card title="Freie Nummern" class="lg:col-span-2">
            <x-slot:actions>
                @if ($frei->isNotEmpty())
                    <form method="POST" action="{{ route('startnummern.destroy-many') }}" onsubmit="return confirm('Alle freien Nummern entfernen?')">@csrf @method('DELETE')<button class="btn-light btn-sm">alle entfernen</button></form>
                @endif
            </x-slot:actions>
            @if ($frei->isEmpty())
                <x-empty message="Keine freien Nummern im Pool." />
            @else
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($frei as $n)
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-sm tabular-nums">{{ $n }}</span>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
