@php $event = $a->event; $konto = $a->konto; $status = $a->status(); @endphp
<x-layouts.public title="Ihre Spende">
    <h1 class="text-3xl">Vielen Dank, {{ $a->sponsor->firmenname ?: trim($a->sponsor->vorname.' '.$a->sponsor->nachname) }}!</h1>
    <p class="mt-2 text-slate-600">Ihre Unterstützung beim {{ $event->name }} am {{ $event->datum->format('d.m.Y') }}.</p>

    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
        <x-stat label="Ihre Spende" :value="euro($konto->sollCent)" accent />
        <x-stat label="Eingegangen" :value="euro($konto->istCent)" />
        <div class="card col-span-2 flex items-center p-4 sm:col-span-1"><x-badge :color="$status->farbe()" class="text-base!">{{ $status->label() }}</x-badge></div>
    </div>

    @if (! $event->laufVorbei())
        <p class="mt-4 rounded-lg bg-navy-50 p-3 text-sm text-navy-800">Der Lauf hat noch nicht stattgefunden – der Betrag steht erst nach dem Lauf fest.</p>
    @endif

    <x-card title="Aufstellung" class="mt-6" :padding="false">
        @include('abrechnung._positionen', ['a' => $a])
    </x-card>

    @if ($event->laufVorbei() && $konto->offenCent() > 0)
        <x-card title="So können Sie bezahlen" class="mt-6">
            <div class="grid gap-6 sm:grid-cols-2">
                @if ($event->iban)
                    <dl class="space-y-1 text-sm">
                        <div><dt class="text-slate-500">Empfänger</dt><dd class="font-medium">{{ $event->kontoinhaber }}</dd></div>
                        <div><dt class="text-slate-500">IBAN</dt><dd class="font-mono">{{ $event->ibanFormatiert() }}</dd></div>
                        @if ($event->bic)<div><dt class="text-slate-500">BIC</dt><dd class="font-mono">{{ $event->bic }}</dd></div>@endif
                        <div><dt class="text-slate-500">Betrag</dt><dd class="font-medium">{{ euro($konto->offenCent()) }}</dd></div>
                        <div><dt class="text-slate-500">Verwendungszweck</dt><dd class="font-medium">{{ $a->verwendungszweck() }}</dd></div>
                    </dl>
                @endif
                @if ($link = $a->paypalLink())
                    <div>
                        <p class="text-sm text-slate-600">Oder bequem per PayPal:</p>
                        <a href="{{ $link }}" target="_blank" rel="noopener" class="btn-secondary btn-lg mt-2">{{ euro($konto->offenCent()) }} mit PayPal bezahlen</a>
                        <p class="hint">Bitte geben Sie bei PayPal als Nachricht „{{ $a->verwendungszweck() }}“ an.</p>
                    </div>
                @endif
            </div>
        </x-card>
    @endif

    @if ($a->nachweisMoeglich())
        <x-card title="Spendennachweis" class="mt-6">
            <p class="text-sm text-slate-600">Bei Spenden bis 300 € genügt dem Finanzamt dieser vereinfachte Nachweis zusammen mit Ihrem Kontoauszug.</p>
            <a href="{{ route('public.sponsor.nachweis', $a->sponsor->uuid) }}" class="btn-primary mt-3"><x-icon name="download" class="size-4" /> Nachweis herunterladen (PDF)</a>
        </x-card>
    @elseif ($konto->istBezahlt())
        <p class="mt-6 text-sm text-slate-600">Für Spenden über 300 € stellt der Verein auf Wunsch eine Zuwendungsbestätigung aus. Bitte wenden Sie sich an {{ $event->absenderAdresse() }}.</p>
    @endif
</x-layouts.public>
