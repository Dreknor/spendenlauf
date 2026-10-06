<x-layouts.app :title="'Team '.$team->name">
    <x-page-header :title="'Team „'.$team->name.'“'" :subtitle="'Verantwortlich: '.($team->besitzer?->name ?? '–')">
        <a href="{{ route('sponsorings.create', ['team' => $team->id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Team-Spende eintragen</a>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Team-Spenden" :value="euro($bilanz->teams[$team->id]['spende'] ?? 0)" accent />
        <x-stat label="Mitglieder" :value="$team->laeufer->count()" />
        <x-stat label="Runden" :value="zahl($team->laeufer->sum('runden'))" />
        <x-stat label="Einzelspenden der Mitglieder" :value="euro($team->laeufer->sum(fn ($l) => $bilanz->laeufer[$l->id]['einzel'] ?? 0))" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Mitglieder" :padding="false">
                @if ($team->laeufer->isEmpty())
                    <div class="p-6"><x-empty message="Noch keine Läufer im Team. Läufer können auf ihrer Seite einem Team beitreten." /></div>
                @else
                    <table class="table">
                        <thead><tr><th>Nr.</th><th>Name</th><th>Klasse</th><th class="num">Runden</th><th class="num">Anteil Team-Spende</th></tr></thead>
                        <tbody>
                        @foreach ($team->laeufer as $l)
                            <tr>
                                <td class="tabular-nums">{{ $l->startnummer }}</td>
                                <td>@can('view', $l)<a href="{{ route('laeufer.edit', $l) }}">{{ $l->name }}</a>@else{{ $l->name }}@endcan</td>
                                <td>{{ $l->schulklasse?->name ?? '–' }}</td>
                                <td class="num">{{ $l->runden }}</td>
                                <td class="num">{{ euro($bilanz->laeufer[$l->id]['teamanteil'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </x-card>
            <x-card title="Team-Spenden" :padding="false">
                @if ($team->sponsorings->isEmpty())
                    <div class="p-6"><x-empty message="Noch keine Team-Spenden." /></div>
                @else
                    @include('sponsorings._tabelle', ['sponsorings' => $team->sponsorings, 'zeigeZiel' => false])
                @endif
            </x-card>
        </div>
        <div class="space-y-6">
            <x-card title="Einstellungen">
                <form method="POST" action="{{ route('teams.update', $team) }}" class="space-y-4">
                    @csrf @method('PUT')
                    <x-input name="name" label="Teamname" :value="$team->name" required />
                    <input type="hidden" name="open" value="0">
                    <x-checkbox name="open" label="Offenes Team" :checked="$team->open" />
                    <button type="submit" class="btn-primary w-full">Speichern</button>
                </form>
            </x-card>
            @if ($team->laeufer->isEmpty() && $team->sponsorings->isEmpty())
                <x-delete-button :action="route('teams.destroy', $team)" label="Team löschen" />
            @endif
        </div>
    </div>
</x-layouts.app>
