<x-layouts.app title="Runden importieren">
    <x-page-header title="Runden importieren" :subtitle="'Aktuell '.zahl($summe).' Runden · '.$manuell.' Läufer mit manuell eingegebenen Runden'" />

    @if ($ergebnis)
        <x-card title="Ergebnis des letzten Imports" class="mb-6">
            <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-5">
                <div><dt class="text-slate-500">Zeilen</dt><dd class="text-xl font-bold">{{ $ergebnis['zeilen'] }}</dd></div>
                <div><dt class="text-slate-500">aktualisiert</dt><dd class="text-xl font-bold text-emerald-700">{{ $ergebnis['aktualisiert'] }}</dd></div>
                <div><dt class="text-slate-500">unverändert</dt><dd class="text-xl font-bold">{{ $ergebnis['unveraendert'] }}</dd></div>
                <div><dt class="text-slate-500">manuell, übersprungen</dt><dd class="text-xl font-bold text-amber-700">{{ count($ergebnis['uebersprungen']) }}</dd></div>
                <div><dt class="text-slate-500">unbekannte Nummern</dt><dd class="text-xl font-bold text-red-700">{{ count($ergebnis['unbekannt']) }}</dd></div>
            </dl>
            @if ($ergebnis['uebersprungen'])<p class="mt-3 text-sm">Übersprungen (manuell): {{ implode(', ', $ergebnis['uebersprungen']) }}</p>@endif
            @if ($ergebnis['unbekannt'])<p class="mt-1 text-sm">Unbekannt: {{ implode(', ', $ergebnis['unbekannt']) }}</p>@endif
        </x-card>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Von der Zeitmessung (URL)">
            <form method="POST" action="{{ route('import.url') }}" class="space-y-4">
                @csrf
                <x-input name="url" type="url" label="Ergebnisseite" :value="$event->import_url" hint="Am Lauftag wird diese Seite automatisch alle 5 Minuten abgefragt." />
                <input type="hidden" name="manuelle_ueberschreiben" value="0">
                <x-checkbox name="manuelle_ueberschreiben" label="auch manuell eingegebene Runden überschreiben" />
                <button type="submit" class="btn-primary">Jetzt importieren</button>
            </form>
        </x-card>
        <x-card title="Aus Datei (CSV / Excel)">
            <form method="POST" action="{{ route('import.datei') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="datei">Datei</label>
                    <input type="file" name="datei" id="datei" accept=".csv,.txt,.xlsx,.xls" required>
                    <p class="hint">Benötigt die Spalten „Startnummer“ (oder „Startnr“) und „Runden“. Die Kopfzeile darf auch weiter unten stehen.</p>
                    @error('datei')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <input type="hidden" name="manuelle_ueberschreiben" value="0">
                <x-checkbox name="manuelle_ueberschreiben" id="manuelle_datei" label="auch manuell eingegebene Runden überschreiben" />
                <button type="submit" class="btn-primary">Datei importieren</button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
