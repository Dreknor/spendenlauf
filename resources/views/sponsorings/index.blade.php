<x-layouts.app title="Spenden">
    <x-page-header title="Spenden" :subtitle="$sponsorings->total().' Zusagen'">
        <a href="{{ route('sponsorings.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Spende eintragen</a>
    </x-page-header>

    <x-search-form placeholder="Sponsor, Läufer oder Team" class="mb-4" />

    @if ($sponsorings->isEmpty())
        <x-empty message="Noch keine Spenden eingetragen." />
    @else
        <div class="table-wrap">
            @include('sponsorings._tabelle')
        </div>
        <div class="mt-4">{{ $sponsorings->links() }}</div>
    @endif
</x-layouts.app>
