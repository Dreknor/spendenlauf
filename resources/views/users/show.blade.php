<x-layouts.app :title="$user->name">
    <x-page-header :title="$user->name" :subtitle="$user->email">
        @unless ($user->email_verified_at)
            <form method="POST" action="{{ route('users.verification', $user) }}">@csrf<button class="btn-light"><x-icon name="mail" class="size-4" /> Bestätigungs-Mail erneut senden</button></form>
        @endunless
    </x-page-header>
    <form method="POST" action="{{ route('users.update', $user) }}" class="grid gap-6 lg:grid-cols-2">
        @csrf @method('PUT')
        <x-card title="Daten">
            <div class="space-y-4">
                <x-input name="vorname" label="Vorname" :value="$user->vorname" required />
                <x-input name="nachname" label="Nachname" :value="$user->nachname" required />
                <x-input name="email" type="email" label="E-Mail" :value="$user->email" required />
            </div>
        </x-card>
        <x-card title="Rechte">
            @can('edit permission')
                <div class="space-y-2">
                    @foreach ($rechte as $name => $beschreibung)
                        <x-checkbox name="permissions[]" :value="$name" :label="$beschreibung" :checked="$user->hasDirectPermission($name)" />
                    @endforeach
                </div>
            @else
                <ul class="list-disc pl-5 text-sm">
                    @forelse ($user->permissions as $p)<li>{{ $rechte[$p->name] ?? $p->name }}</li>@empty<li>keine</li>@endforelse
                </ul>
            @endcan
        </x-card>
        <div><button type="submit" class="btn-primary">Speichern</button></div>
    </form>
</x-layouts.app>
