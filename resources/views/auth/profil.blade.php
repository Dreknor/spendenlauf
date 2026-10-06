<x-layouts.app title="Mein Profil">
    <x-page-header title="Mein Profil" />
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Persönliche Daten">
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="vorname" label="Vorname" :value="auth()->user()->vorname" required />
                    <x-input name="nachname" label="Nachname" :value="auth()->user()->nachname" required />
                </div>
                <x-input name="email" type="email" label="E-Mail-Adresse" :value="auth()->user()->email" required
                         hint="Bei Änderung müssen Sie die neue Adresse erneut bestätigen." />
                @foreach ($errors->updateProfileInformation->all() as $fehler)
                    <p class="field-error">{{ $fehler }}</p>
                @endforeach
                <button type="submit" class="btn-primary">Speichern</button>
            </form>
        </x-card>
        <x-card title="Passwort ändern">
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-input name="current_password" type="password" label="Aktuelles Passwort" required autocomplete="current-password" />
                <x-input name="password" type="password" label="Neues Passwort" required autocomplete="new-password" />
                <x-input name="password_confirmation" type="password" label="Neues Passwort wiederholen" required autocomplete="new-password" />
                @foreach ($errors->updatePassword->all() as $fehler)
                    <p class="field-error">{{ $fehler }}</p>
                @endforeach
                <button type="submit" class="btn-primary">Passwort ändern</button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
