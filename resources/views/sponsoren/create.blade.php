<x-layouts.app title="Sponsor anlegen">
    <x-page-header title="Sponsor anlegen" />
    <form method="POST" action="{{ route('sponsoren.store') }}" class="max-w-2xl space-y-6">
        @csrf
        <input type="hidden" name="weiter" value="{{ request('weiter') }}">
        <input type="hidden" name="laeufer" value="{{ request('laeufer') }}">
        <x-card>
            @include('sponsoren._form', ['sponsor' => null])
            <p class="hint mt-4">Bitte nur mit Einverständnis des Sponsors eintragen. Die Daten werden ausschließlich für die Abwicklung des Spendenlaufs verwendet.</p>
        </x-card>
        <button type="submit" class="btn-primary">{{ request('weiter') === 'sponsoring' ? 'Speichern und Spende eintragen' : 'Sponsor speichern' }}</button>
    </form>
</x-layouts.app>
