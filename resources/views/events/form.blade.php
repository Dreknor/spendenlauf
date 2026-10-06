@php
    $neu = ! $event->exists;
    $grenzen = collect($event->altersgruppen ?: \App\Models\Event::STANDARD_ALTERSGRUPPEN)->pluck('bis')->filter()->join(', ');
@endphp
<x-layouts.app :title="$neu ? 'Neuer Lauf' : $event->name">
    <x-page-header :title="$neu ? 'Neuen Spendenlauf vorbereiten' : 'Einstellungen: '.$event->name"
                   :subtitle="$neu && $vorlage ? 'Die Einstellungen wurden von „'.$vorlage->name.'“ übernommen.' : null" />

    <form method="POST" action="{{ $neu ? route('events.store') : route('events.update', $event) }}" class="space-y-6">
        @csrf
        @unless ($neu) @method('PUT') @endunless

        <x-card title="Allgemein">
            <div class="grid gap-4 sm:grid-cols-4">
                <x-input name="name" label="Name" :value="$event->name" required class="sm:col-span-2" />
                <x-input name="nummer" type="number" min="1" label="Nr." :value="$event->nummer" />
                <x-input name="datum" type="date" label="Lauftag" :value="$event->datum?->format('Y-m-d')" required />
                <x-input name="anmeldeschluss" type="date" label="Anmeldeschluss" :value="$event->anmeldeschluss?->format('Y-m-d')" hint="Leer = bis zum Lauftag" />
                <x-input name="runden_laenge_m" type="number" min="1" label="Rundenlänge (m)" :value="$event->runden_laenge_m" required />
                <x-input name="dauer_minuten" type="number" min="1" label="Laufzeit (Minuten)" :value="$event->dauer_minuten" required />
                <x-input name="altersgruppen_text" label="Altersgruppen (Obergrenzen)" :value="$grenzen" hint="z. B. 6, 10, 14, 18, 39, 59 → bis 6, 7–10, …, ab 60" />
            </div>
        </x-card>

        <x-card title="Verteilung der Spenden" subtitle="Beide Anteile werden vom Bruttobetrag berechnet, der Rest geht an die Projekte.">
            <div class="grid gap-4 sm:grid-cols-4">
                <x-input name="soziale_abgabe_prozent" type="number" step="0.01" min="0" max="100" label="Soziale Abgabe (%)" :value="$event->soziale_abgabe_prozent ?? 0" required />
                <x-input name="soziale_abgabe_empfaenger" label="Empfänger der sozialen Abgabe" :value="$event->soziale_abgabe_empfaenger" class="sm:col-span-3" />
                <x-textarea name="soziale_abgabe_beschreibung" label="Beschreibung (öffentlich)" :value="$event->soziale_abgabe_beschreibung" rows="2" class="sm:col-span-4" />
                <x-input name="klassenkasse_prozent" type="number" step="0.01" min="0" max="100" label="Klassenkasse (%)" :value="$event->klassenkasse_prozent ?? 10" required
                         hint="Anteil der Spenden von Schüler-Läufern für ihre Klasse" class="sm:col-span-2" />
            </div>
        </x-card>

        <x-card title="Projekte in diesem Lauf">
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($projekte as $p)
                    <x-checkbox name="projects[]" :value="(string) $p->id" :label="$p->name" :checked="in_array($p->id, $gewaehlteProjekte)" />
                @endforeach
            </div>
            @if ($projekte->isEmpty())<p class="text-sm text-slate-500">Noch keine Projekte angelegt.</p>@endif
        </x-card>

        <x-card title="Zahlung" subtitle="Erscheint in der Zahlungsaufforderung und auf der Sponsor-Seite.">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-input name="kontoinhaber" label="Kontoinhaber" :value="$event->kontoinhaber" />
                <x-input name="iban" label="IBAN" :value="$event->ibanFormatiert()" />
                <x-input name="bic" label="BIC" :value="$event->bic" />
                <x-input name="bank" label="Bank" :value="$event->bank" />
                <x-input name="verwendungszweck_prefix" label="Verwendungszweck" :value="$event->verwendungszweck_prefix" required hint="Wird ergänzt um Jahr und Sponsor-Nr., z. B. „Spendenlauf 2027 Nr. 123“" />
                <x-input name="zahlungsziel_tage" type="number" min="1" label="Zahlungsziel (Tage)" :value="$event->zahlungsziel_tage" required />
                <x-input name="paypal_me_name" label="PayPal.me-Name" :value="$event->paypal_me_name" hint="z. B. „FoerdervereinESZ“ für paypal.me/FoerdervereinESZ" />
            </div>
        </x-card>

        <x-card title="Verein (für den vereinfachten Spendennachweis)">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-input name="verein_name" label="Name des Vereins" :value="$event->verein_name" />
                <x-input name="verein_anschrift" label="Anschrift" :value="$event->verein_anschrift" class="sm:col-span-2" />
                <x-input name="verein_steuernummer" label="Steuernummer" :value="$event->verein_steuernummer" />
                <x-input name="verein_finanzamt" label="Finanzamt" :value="$event->verein_finanzamt" />
                <x-input name="verein_freistellung_datum" type="date" label="Freistellungsbescheid vom" :value="$event->verein_freistellung_datum?->format('Y-m-d')" />
                <x-input name="verein_zweck" label="Steuerbegünstigter Zweck" :value="$event->verein_zweck" class="sm:col-span-3" hint="z. B. „Förderung der Erziehung und Bildung“" />
            </div>
        </x-card>

        <x-card title="Kommunikation & Zeitmessung">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="mail_absender_name" label="Absendername der Mails" :value="$event->mail_absender_name" />
                <x-input name="mail_absender_adresse" type="email" label="Absenderadresse" :value="$event->mail_absender_adresse" />
                <x-input name="info_url" type="url" label="Webseite für Fotos & Infos" :value="$event->info_url" />
                <x-input name="import_url" type="url" label="Ergebnisseite der Zeitmessung" :value="$event->import_url" hint="Wird am Lauftag alle 5 Minuten abgefragt." />
            </div>
        </x-card>

        @if ($neu && $vorlage)
            <x-card title="Übernahme aus „{{ $vorlage->name }}“">
                <input type="hidden" name="vorlage_event_id" value="{{ $vorlage->id }}">
                <input type="hidden" name="klassen_uebernehmen" value="0">
                <x-checkbox name="klassen_uebernehmen" label="Klassen übernehmen und entsprechend der Jahre hochstufen (z. B. 5a → 7a)" checked />
            </x-card>
        @endif

        <div class="flex gap-2">
            <button type="submit" class="btn-primary">{{ $neu ? 'Lauf anlegen' : 'Speichern' }}</button>
            <a href="{{ route('events.index') }}" class="btn-light">Abbrechen</a>
        </div>
    </form>
</x-layouts.app>
