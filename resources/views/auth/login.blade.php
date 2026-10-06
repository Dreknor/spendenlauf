<x-layouts.guest title="Anmelden">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-input name="email" type="email" label="E-Mail-Adresse" required autofocus autocomplete="username" />
        <x-input name="password" type="password" label="Passwort" required autocomplete="current-password" />
        <x-checkbox name="remember" label="Angemeldet bleiben" />
        <button type="submit" class="btn-primary w-full">Anmelden</button>
    </form>
    <div class="mt-6 flex flex-wrap justify-between gap-2 text-sm">
        <a href="{{ route('password.request') }}">Passwort vergessen?</a>
        <a href="{{ route('register') }}">Neues Konto registrieren</a>
    </div>
</x-layouts.guest>
