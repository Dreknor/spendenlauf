<x-layouts.public title="Spendenzusage bestätigen">
    <div class="mx-auto max-w-xl">
        @if (! $zusage)
            <x-card title="Link nicht mehr gültig">
                <p class="text-slate-600">Dieser Link ist ungültig oder abgelaufen, oder die Spendenzusage wurde bereits bestätigt. Bei Fragen wenden Sie sich bitte an das Orga-Team des Spendenlaufs.</p>
            </x-card>
        @elseif ($vorbei)
            <x-card title="Spendenlauf vorbei">
                <p class="text-slate-600">Der {{ $zusage->event->name }} ist bereits vorbei – diese Zusage kann nicht mehr bestätigt werden. Vielen Dank für Ihr Interesse!</p>
            </x-card>
        @else
            @php $daten = $zusage->daten; @endphp
            <x-card title="Spendenzusage bestätigen">
                <p class="text-slate-600">
                    {{ $zusage->sponsorDaten()->name }} unterstützt <strong>{{ $zusage->laeufer->vorname }} {{ mb_substr($zusage->laeufer->nachname, 0, 1) }}.</strong>
                    beim {{ $zusage->event->name }} am {{ $zusage->event->datum->format('d.m.Y') }}:
                </p>
                <dl class="mt-4 space-y-1 text-sm">
                    @if ((float) ($daten['rundenBetrag'] ?? 0) > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">je Runde</dt><dd>{{ euro($daten['rundenBetrag'], false) }}</dd></div>
                    @endif
                    @if ((float) ($daten['festBetrag'] ?? 0) > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Festbetrag</dt><dd>{{ euro($daten['festBetrag'], false) }}</dd></div>
                    @endif
                    @if ((float) ($daten['maxBetrag'] ?? 0) > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">höchstens</dt><dd>{{ euro($daten['maxBetrag'], false) }}</dd></div>
                    @endif
                </dl>
                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                    @csrf
                    <button type="submit" class="btn-primary btn-lg w-full">Spende verbindlich zusagen</button>
                </form>
                <p class="mt-3 text-xs text-slate-500">Nach dem Lauf erhalten Sie eine E-Mail mit dem erlaufenen Betrag und den Zahlungsinformationen. Haben Sie keine Spende zugesagt, schließen Sie diese Seite einfach – Ihre Angaben werden dann automatisch gelöscht.</p>
            </x-card>
        @endif
    </div>
</x-layouts.public>
