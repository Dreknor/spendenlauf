<x-layouts.app title="Benutzer">
    <x-page-header title="Benutzer" :subtitle="$users->total().' Konten'" />
    <x-search-form placeholder="Name oder E-Mail" class="mb-4">
        <x-select name="filter" :options="['team' => 'nur mit besonderen Rechten']" :selected="request('filter')" placeholder="alle Benutzer" class="w-60" />
    </x-search-form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>E-Mail</th><th>Bestätigt</th><th class="num">Läufer</th><th>Rechte</th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td><a href="{{ route('users.show', $u) }}" class="font-medium">{{ $u->nachname }}, {{ $u->vorname }}</a></td>
                    <td class="text-slate-600">{{ $u->email }}</td>
                    <td>@if ($u->email_verified_at)<x-badge color="green">ja</x-badge>@else<x-badge color="amber">nein</x-badge>@endif</td>
                    <td class="num">{{ $u->laeufer_count }}</td>
                    <td class="text-xs text-slate-500">{{ $u->permissions->count() ? $u->permissions->count().' Rechte' : '–' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
