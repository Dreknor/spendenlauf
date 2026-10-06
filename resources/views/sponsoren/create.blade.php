<x-layouts.app title="Sponsor anlegen">
    <x-page-header title="Sponsor anlegen" />
    <form method="POST" action="{{ route('sponsoren.store') }}" class="max-w-2xl space-y-6">
        @csrf
        <input type="hidden" name="weiter" value="{{ request('weiter') }}">
        <input type="hidden" name="laeufer" value="{{ request('laeufer') }}">
        @if (session('sponsor_dubletten'))
            <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 sm:p-5">
                <p class="font-semibold text-amber-900">Es gibt bereits ähnliche Sponsoren. Ist einer davon gemeint?</p>
                <div class="mt-3 space-y-2 text-sm">
                    @foreach (session('sponsor_dubletten') as $d)
                        <label class="flex items-center gap-2 rounded-lg bg-white p-3 font-normal ring-1 ring-amber-100">
                            <input type="radio" name="vorhandener_sponsor" value="{{ $d['id'] }}" checked>
                            <span><strong>{{ $d['name'] }}</strong> {{ $d['ort'] }}</span>
                        </label>
                    @endforeach
                    <label class="flex items-center gap-2 rounded-lg bg-white p-3 font-normal ring-1 ring-amber-100">
                        <input type="radio" name="vorhandener_sponsor" value="" onchange="document.getElementById('trotzdem_anlegen').value = 1">
                        <span>Nein, neuen Sponsor anlegen</span>
                    </label>
                    <input type="hidden" name="trotzdem_anlegen" id="trotzdem_anlegen" value="0">
                </div>
            </div>
        @endif
        <x-card>
            @include('sponsoren._form', ['sponsor' => null])
            <p class="hint mt-4">Bitte nur mit Einverständnis des Sponsors eintragen. Die Daten werden ausschließlich für die Abwicklung des Spendenlaufs verwendet.</p>
        </x-card>
        <button type="submit" class="btn-primary">{{ request('weiter') === 'sponsoring' ? 'Speichern und Spende eintragen' : 'Sponsor speichern' }}</button>
    </form>
</x-layouts.app>
