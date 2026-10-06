<x-layouts.app title="Team gründen">
    <x-page-header title="Team gründen" />
    <form method="POST" action="{{ route('teams.store') }}" class="max-w-xl space-y-6">
        @csrf
        <x-card>
            <div class="space-y-4">
                <x-input name="name" label="Teamname" required autofocus />
                <input type="hidden" name="open" value="0">
                <x-checkbox name="open" label="Offenes Team – andere Eltern können ihre Läufer selbst hinzufügen" checked />
            </div>
        </x-card>
        <button type="submit" class="btn-primary">Team speichern</button>
    </form>
</x-layouts.app>
