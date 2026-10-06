<x-layouts.guest title="Neues Passwort">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-input name="email" type="email" label="E-Mail-Adresse" :value="$request->email" required />
        <x-input name="password" type="password" label="Neues Passwort" required autocomplete="new-password" />
        <x-input name="password_confirmation" type="password" label="Passwort wiederholen" required autocomplete="new-password" />
        <button type="submit" class="btn-primary w-full">Passwort speichern</button>
    </form>
</x-layouts.guest>
