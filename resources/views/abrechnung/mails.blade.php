<x-layouts.app :title="$typ->label().'en'">
    <x-page-header :title="$typ->label().'en versenden'" :subtitle="$empfaenger->count().' Empfänger'">
        <a href="{{ route('abrechnung.index') }}" class="btn-light">Zurück</a>
    </x-page-header>

    <x-card class="mb-6">
        @if ($typ === \App\Enums\MailTyp::Aufforderung)
            <p class="text-sm text-slate-600">Sponsoren mit E-Mail-Adresse und offenem Betrag, die noch keine Zahlungsaufforderung erhalten haben. Die Mail enthält Dank, Aufstellung, Bankverbindung, Verwendungszweck{{ $event->paypal_me_name ? ', PayPal-Link' : '' }} und den Link zur persönlichen Sponsor-Seite.</p>
            @unless ($event->laufVorbei())
                <p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Achtung: Der Lauf hat noch nicht stattgefunden – die Rundenbeträge stehen noch nicht fest.</p>
            @endunless
        @else
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="typ" value="erinnerung">
                <x-input name="tage" type="number" min="0" label="Letzte Mail älter als (Tage)" :value="$tage" class="w-56" />
                <button class="btn-light">Aktualisieren</button>
            </form>
        @endif
        @if (config('queue.default') === 'sync' && $empfaenger->count() > 20)
            <p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Die Mails werden ohne Warteschlange direkt beim Absenden verschickt. Bei vielen Empfängern kann das Zeitlimit des Servers greifen – dann einfach erneut absenden, bereits versandte Mails werden übersprungen. Besser: <code>QUEUE_CONNECTION=database</code> in der <code>.env</code> (siehe README).</p>
        @endif
        @if (blank($event->iban) && blank($event->paypal_me_name))
            <p class="mt-2 rounded-lg bg-red-50 p-3 text-sm text-red-800">Für die Veranstaltung sind keine Bankdaten hinterlegt. <a href="{{ route('events.edit', $event) }}">Jetzt ergänzen</a></p>
        @endif
    </x-card>

    @if ($empfaenger->isEmpty())
        <x-empty message="Keine passenden Empfänger." />
    @else
        <form method="POST" action="{{ route('abrechnung.mails.senden') }}" x-data="{ alle: true }" onsubmit="return confirm('Mails jetzt versenden?')">
            @csrf
            <input type="hidden" name="typ" value="{{ $typ->value }}">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th><input type="checkbox" x-model="alle" @change="$root.querySelectorAll('input[name=\'sponsoren[]\']').forEach(c => c.checked = alle)" aria-label="alle"></th>
                        <th>Sponsor</th><th>E-Mail</th><th class="num">offen</th><th>letzte Mail</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($empfaenger as $k)
                        <tr>
                            <td><input type="checkbox" name="sponsoren[]" value="{{ $k['sponsor']->id }}" checked aria-label="auswählen"></td>
                            <td><a href="{{ route('abrechnung.show', $k['sponsor']) }}">{{ $k['sponsor']->name }}</a></td>
                            <td>{{ $k['sponsor']->email }}</td>
                            <td class="num">{{ euro($k['konto']->offenCent()) }}</td>
                            <td class="text-xs text-slate-500">{{ collect([$k['aufforderung'], $k['erinnerung']])->filter()->max()?->format('d.m.Y') ?? '–' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn-primary mt-4"><x-icon name="mail" class="size-4" /> Ausgewählte senden</button>
        </form>
    @endif
</x-layouts.app>
