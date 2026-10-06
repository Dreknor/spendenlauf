<x-layouts.guest title="Registrieren">
    <p class="mb-4 text-sm text-slate-600">
        Mit einem Konto können Sie sich selbst und Ihre Kinder für den Spendenlauf anmelden und Sponsoren verwalten.
    </p>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <x-honeypot />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input name="vorname" label="Vorname" required autofocus autocomplete="given-name" />
            <x-input name="nachname" label="Nachname" required autocomplete="family-name" />
        </div>
        <x-input name="email" type="email" label="E-Mail-Adresse" required autocomplete="email" />
        <x-input name="password" type="password" label="Passwort" required autocomplete="new-password" hint="Mindestens 8 Zeichen." />
        <x-input name="password_confirmation" type="password" label="Passwort wiederholen" required autocomplete="new-password" />
        <x-checkbox name="datenschutz">
            <x-slot:label>Ich habe die <a href="{{ config('spendenlauf.datenschutz_link') }}" target="_blank" rel="noopener">Datenschutzerklärung</a> gelesen und bin einverstanden.</x-slot:label>
        </x-checkbox>
        <button type="submit" class="btn-primary w-full">Konto anlegen</button>
    </form>
    <p class="mt-6 text-sm"><a href="{{ route('login') }}">Ich habe bereits ein Konto</a></p>
</x-layouts.guest>
