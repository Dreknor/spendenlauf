<x-layouts.guest title="E-Mail bestätigen">
    <p class="text-sm text-slate-600">
        Vielen Dank für Ihre Registrierung! Bitte bestätigen Sie Ihre E-Mail-Adresse über den Link, den wir Ihnen gerade geschickt haben.
        Keine Mail erhalten? Bitte prüfen Sie auch den Spam-Ordner.
    </p>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary">Link erneut senden</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-light">Abmelden</button>
        </form>
    </div>
</x-layouts.guest>
