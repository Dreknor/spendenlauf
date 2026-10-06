<x-layouts.app title="Läufer anmelden">
    <x-page-header title="Läufer anmelden" :subtitle="$event->name.' am '.$event->datum->format('d.m.Y')" />

    <form method="POST" action="{{ route('laeufer.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        <x-card title="Angaben zum Läufer" class="lg:col-span-2">
            @include('laeufer._form', ['laeufer' => null])
        </x-card>

        <div class="space-y-6">
            <x-card title="Team (optional)">
                <div x-data="{ modus: @js(old('teamname') ? 'neu' : 'vorhanden') }" class="space-y-3">
                    <div class="flex gap-4 text-sm">
                        <label class="flex items-center gap-2 font-normal"><input type="radio" value="vorhanden" x-model="modus"> bestehendes Team</label>
                        <label class="flex items-center gap-2 font-normal"><input type="radio" value="neu" x-model="modus"> neues Team</label>
                    </div>
                    <div x-show="modus === 'vorhanden'">
                        <x-select name="team_id" :options="$teams->pluck('name', 'id')" placeholder="kein Team" />
                    </div>
                    <div x-show="modus === 'neu'" x-cloak>
                        <x-input name="teamname" label="Name des neuen Teams" />
                    </div>
                    <p class="hint">Bei Team-Spenden zählen die Runden aller Teammitglieder.</p>
                </div>
            </x-card>

            <x-card title="Einverständnis">
                <div class="space-y-3 text-sm">
                    <x-checkbox name="datenschutz">
                        <x-slot:label>Ich bin mit der Verarbeitung der Daten gemäß <a href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutzerklärung</a> einverstanden. <span class="text-esz-600">*</span></x-slot:label>
                    </x-checkbox>
                    <x-checkbox name="bilder" label="Ich bin einverstanden, dass Fotos vom Lauf veröffentlicht werden. *" />
                </div>
            </x-card>

            <button type="submit" class="btn-primary btn-lg w-full">Läufer anmelden</button>
        </div>
    </form>
</x-layouts.app>
