<x-layouts.guest title="Passwort vergessen">
    <p class="mb-4 text-sm text-slate-600">Geben Sie Ihre E-Mail-Adresse ein. Wir senden Ihnen einen Link zum Zurücksetzen des Passworts.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-input name="email" type="email" label="E-Mail-Adresse" required autofocus />
        <button type="submit" class="btn-primary w-full">Link senden</button>
    </form>
    <p class="mt-6 text-sm"><a href="{{ route('login') }}">Zurück zur Anmeldung</a></p>
</x-layouts.guest>
