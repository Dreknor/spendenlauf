{{-- Gemeinsame Felder für Anlegen/Bearbeiten eines Läufers --}}
@php
    $kategorieWert = old('kategorie', $laeufer?->kategorie?->value);
@endphp
<div x-data="{ kategorie: @js($kategorieWert) }" class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-input name="vorname" label="Vorname" :value="$laeufer?->vorname" required />
        <x-input name="nachname" label="Nachname" :value="$laeufer?->nachname" required />
        <x-input name="geburtsdatum" type="date" label="Geburtsdatum" :value="$laeufer?->geburtsdatum?->format('Y-m-d')"
                 :required="! auth()->user()->can('edit laeufer')" hint="Für die Auswertung nach Altersgruppen und die Urkunde." />
        <x-select name="geschlecht" label="Geschlecht" :options="\App\Services\Statistik::GESCHLECHTER" :selected="$laeufer?->geschlecht" placeholder="keine Angabe" />
    </div>

    <fieldset>
        <legend class="text-sm font-medium text-slate-700">Wer läuft? <span class="text-esz-600">*</span></legend>
        <div class="mt-2 grid gap-2 sm:grid-cols-3">
            @foreach (\App\Enums\Kategorie::cases() as $k)
                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm font-normal ring-1 ring-slate-200 has-checked:bg-esz-50 has-checked:ring-esz-400">
                    <input type="radio" name="kategorie" value="{{ $k->value }}" x-model="kategorie" @checked($kategorieWert === $k->value) required>
                    {{ $k->label() }}
                </label>
            @endforeach
        </div>
        @error('kategorie')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>

    <div x-show="kategorie === 'schueler'" x-cloak>
        @if ($klassen->isEmpty())
            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Für diesen Lauf sind noch keine Klassen angelegt.</p>
        @else
            <x-select name="schulklasse_id" label="Klasse" :options="$klassen" :selected="$laeufer?->schulklasse_id" placeholder="bitte wählen"
                      hint="Die Klasse erhält einen Anteil der erlaufenen Spenden für die Klassenkasse." />
        @endif
    </div>

    <x-input name="email" type="email" label="E-Mail des Läufers (optional)" :value="$laeufer?->email" />

    @canany(['edit startnummer', 'edit runden'])
        <div class="grid gap-4 rounded-lg bg-slate-50 p-4 sm:grid-cols-2">
            @can('edit startnummer')
                <x-input name="startnummer" type="number" min="1" label="Startnummer" :value="$laeufer?->startnummer"
                         hint="Leer lassen für die nächste freie Nummer." />
            @endcan
            @if ($laeufer && auth()->user()->can('edit runden'))
                <x-input name="runden" type="number" min="0" label="Runden" :value="$laeufer->runden"
                         hint="Manuell geänderte Runden werden beim Import der Zeitmessung nicht überschrieben." />
            @endif
        </div>
    @endcanany
</div>
