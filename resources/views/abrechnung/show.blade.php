@php $s = $a->sponsor; $konto = $a->konto; @endphp
<x-layouts.app :title="'Abrechnung '.$s->name">
    <x-page-header :title="$s->name" :subtitle="'Verwendungszweck: '.$a->verwendungszweck()">
        <a href="{{ route('abrechnung.index') }}" class="btn-light">Zurück</a>
        <a href="{{ $a->portalUrl() }}" target="_blank" class="btn-light"><x-icon name="external" class="size-4" /> Sponsor-Seite</a>
        @if ($konto->istBezahlt())
            <a href="{{ route('abrechnung.nachweis', $s) }}" target="_blank" class="btn-light"><x-icon name="document" class="size-4" /> Nachweis</a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Soll" :value="euro($konto->sollCent)" accent />
        <x-stat label="Bezahlt" :value="euro($konto->istCent)" />
        <x-stat label="Offen" :value="euro($konto->offenCent())" />
        <div class="card flex items-center p-4"><x-badge :color="$a->status()->farbe()" class="text-sm!">{{ $a->status()->label() }}</x-badge></div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Spenden" :padding="false">
                @include('abrechnung._positionen', ['a' => $a])
            </x-card>

            <x-card title="Zahlungen" :padding="false">
                <table class="table">
                    <thead><tr><th>Datum</th><th>Art</th><th>Notiz</th><th class="num">Betrag</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($a->zahlungen as $z)
                        <tr>
                            <td>{{ $z->bezahlt_am->format('d.m.Y') }}</td>
                            <td>{{ $z->art->label() }}</td>
                            <td class="text-slate-600">{{ $z->notiz }}</td>
                            <td class="num">{{ euro($z->betrag, false) }}</td>
                            <td class="text-right"><x-delete-button :action="route('zahlungen.destroy', $z)" label="" confirm="Zahlung löschen?" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">Noch keine Zahlung erfasst.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <form method="POST" action="{{ route('zahlungen.store', $s) }}" class="grid gap-3 border-t border-slate-100 p-4 sm:grid-cols-5 sm:items-end">
                    @csrf
                    <x-input name="betrag" type="number" step="0.01" label="Betrag (€)" :value="$konto->offenCent() ? number_format($konto->offenCent() / 100, 2, '.', '') : ''" required />
                    <x-input name="bezahlt_am" type="date" label="Datum" :value="now()->format('Y-m-d')" required />
                    <x-select name="art" label="Art" :options="$arten" selected="ueberweisung" />
                    <x-input name="notiz" label="Notiz" />
                    <button type="submit" class="btn-primary">Erfassen</button>
                </form>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Kontakt">
                <p class="text-sm">{!! nl2br(e(implode("\n", $s->anschrift_zeilen))) !!}</p>
                <p class="mt-2 text-sm">{{ $s->email ?? 'keine E-Mail-Adresse' }}@if ($s->telefon)<br>{{ $s->telefon }}@endif</p>
                <a href="{{ route('sponsoren.edit', $s) }}" class="mt-3 inline-block text-sm">Daten bearbeiten</a>
            </x-card>
            <x-card title="Mails">
                @if ($s->email && $konto->offenCent() > 0)
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('abrechnung.mail', $s) }}">@csrf<input type="hidden" name="typ" value="aufforderung"><button class="btn-secondary btn-sm">Zahlungsaufforderung senden</button></form>
                        <form method="POST" action="{{ route('abrechnung.mail', $s) }}">@csrf<input type="hidden" name="typ" value="erinnerung"><button class="btn-light btn-sm">Erinnerung senden</button></form>
                    </div>
                @endif
                <ul class="mt-3 space-y-1 text-sm">
                    @forelse ($mails as $m)
                        <li>{{ $m->gesendet_am->format('d.m.Y H:i') }} · {{ $m->typ->label() }}@if ($m->betrag) ({{ euro($m->betrag, false) }})@endif</li>
                    @empty
                        <li class="text-slate-500">Noch keine Mail versandt.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
