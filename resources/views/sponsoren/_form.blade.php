@php $anrede = old('anrede', $sponsor?->anrede ?? 'Frau'); @endphp
<div x-data="{ anrede: @js($anrede) }" class="space-y-4">
    <fieldset>
        <legend class="text-sm font-medium text-slate-700">Anrede <span class="text-esz-600">*</span></legend>
        <div class="mt-2 flex flex-wrap gap-4 text-sm">
            @foreach (['Frau', 'Herr', 'Firma'] as $a)
                <label class="flex items-center gap-2 font-normal"><input type="radio" name="anrede" value="{{ $a }}" x-model="anrede" @checked($anrede === $a)> {{ $a === 'Firma' ? 'Firma / Organisation' : $a }}</label>
            @endforeach
        </div>
    </fieldset>
    <div x-show="anrede === 'Firma'" x-cloak>
        <x-input name="firmenname" label="Firma / Organisation" :value="$sponsor?->firmenname" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-input name="vorname" label="Vorname" :value="$sponsor?->vorname" x-bind:required="anrede !== 'Firma'" />
        <x-input name="nachname" label="Nachname" :value="$sponsor?->nachname" x-bind:required="anrede !== 'Firma'" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-input name="email" type="email" label="E-Mail" :value="$sponsor?->email" hint="Für Dank und Zahlungsinformationen nach dem Lauf." />
        <x-input name="telefon" type="tel" label="Telefon" :value="$sponsor?->telefon" />
    </div>
    <x-input name="strasse" label="Straße und Hausnummer" :value="$sponsor?->strasse" required />
    <div class="grid gap-4 sm:grid-cols-3">
        <x-input name="plz" label="PLZ" :value="$sponsor?->plz" required />
        <x-input name="ort" label="Ort" :value="$sponsor?->ort" class="sm:col-span-2" required />
    </div>
</div>
