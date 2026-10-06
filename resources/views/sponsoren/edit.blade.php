<x-layouts.app :title="$sponsor->name">
    <x-page-header :title="$sponsor->name" :subtitle="trim($sponsor->plz.' '.$sponsor->ort)">
        <a href="{{ route('sponsorings.create', ['sponsor' => $sponsor->id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Spende eintragen</a>
        @can('manage abrechnung')
            <a href="{{ route('abrechnung.show', $sponsor) }}" class="btn-light"><x-icon name="banknotes" class="size-4" /> Abrechnung</a>
        @endcan
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="grid grid-cols-3 gap-4">
                <x-stat label="Spendensumme" :value="euro($konto->sollCent)" accent />
                <x-stat label="Bezahlt" :value="euro($konto->istCent)" />
                <x-stat label="Status" :value="$konto->status()->label()" />
            </div>
            <x-card title="Spenden" :padding="false">
                @if ($sponsor->sponsorings->isEmpty())
                    <div class="p-6"><x-empty message="Für diesen Lauf noch keine Spenden." /></div>
                @else
                    @include('sponsorings._tabelle', ['sponsorings' => $sponsor->sponsorings, 'zeigeSponsor' => false])
                @endif
            </x-card>
        </div>
        <x-card title="Kontaktdaten">
            @can('update', $sponsor)
                <form method="POST" action="{{ route('sponsoren.update', $sponsor) }}" class="space-y-4">
                    @csrf @method('PUT')
                    @include('sponsoren._form')
                    <button type="submit" class="btn-primary w-full">Speichern</button>
                </form>
            @else
                <p class="text-sm">{!! nl2br(e(implode("\n", $sponsor->anschrift_zeilen))) !!}</p>
            @endcan
        </x-card>
    </div>
</x-layouts.app>
