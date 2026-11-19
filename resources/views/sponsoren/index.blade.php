<x-layouts.app title="Sponsoren">
    <x-page-header title="Sponsoren" :subtitle="$alleLaeufe ? $sponsoren->total().' Sponsoren aus allen Läufen' : $sponsoren->total().' Sponsoren in „'.$event->name.'“'">
        <a href="{{ route('sponsoren.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Sponsor anlegen</a>
    </x-page-header>

    <x-search-form placeholder="Name, Firma, E-Mail, Ort" class="mb-4">
        <x-select name="filter" :options="['alle' => 'alle Sponsoren (auch frühere Läufe)']" :selected="$alleLaeufe ? 'alle' : null" placeholder="nur dieser Lauf" class="w-72" />
    </x-search-form>

    @if ($ausgeblendet > 0)
        <p class="mb-4 text-sm text-slate-600">
            {{ $ausgeblendet === 1 ? 'Ein Sponsor' : zahl($ausgeblendet).' Sponsoren' }} aus früheren Läufen {{ $ausgeblendet === 1 ? 'ist' : 'sind' }} ausgeblendet.
            <a href="{{ route('sponsoren.index', ['filter' => 'alle']) }}">Alle anzeigen</a>
        </p>
    @endif

    @if ($sponsoren->isEmpty())
        <x-empty message="Keine Sponsoren gefunden." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Name</th><th>Ort</th><th>E-Mail</th><th class="num">Spenden</th><th class="num">Summe</th></tr></thead>
                <tbody>
                @foreach ($sponsoren as $s)
                    <tr>
                        <td><a href="{{ route('sponsoren.edit', $s) }}" class="font-medium">{{ $s->name }}</a></td>
                        <td class="text-slate-600">{{ trim($s->plz.' '.$s->ort) ?: '–' }}</td>
                        <td class="text-slate-600">@can('update', $s){{ $s->email ?? '–' }}@else–@endcan</td>
                        <td class="num">{{ $s->sponsorings_count }}</td>
                        <td class="num">{{ euro($summen[$s->id] ?? 0) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sponsoren->links() }}</div>
    @endif
</x-layouts.app>
