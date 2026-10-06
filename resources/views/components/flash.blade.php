@php
    // Einheitliche Meldungen: session('status') + session('type'); ältere Keys werden unterstützt
    $meldung = session('status') ?? session('Meldung') ?? session('message');
    $typ = session('type', 'success');
    $farben = [
        'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'danger' => 'bg-red-50 text-red-800 ring-red-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'info' => 'bg-navy-50 text-navy-800 ring-navy-200',
    ];
    // Fortify-Statusmeldungen übersetzen
    $meldung = match ($meldung) {
        'verification-link-sent' => 'Ein neuer Bestätigungslink wurde an Ihre E-Mail-Adresse gesendet.',
        'profile-information-updated' => 'Profil gespeichert.',
        'password-updated' => 'Passwort geändert.',
        default => $meldung,
    };
@endphp
@if ($meldung)
    <div x-data="{ show: true }" x-show="show" x-transition
         class="mb-4 flex items-start gap-3 rounded-lg p-4 text-sm ring-1 {{ $farben[$typ] ?? $farben['info'] }}" role="status">
        <x-icon :name="$typ === 'success' ? 'check' : ($typ === 'danger' ? 'warning' : 'info')" class="size-5 shrink-0" />
        <div class="flex-1">{{ $meldung }}</div>
        <button type="button" @click="show = false" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Schließen">
            <x-icon name="x" class="size-4" />
        </button>
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">
        <p class="font-semibold">Bitte prüfen Sie Ihre Eingaben:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
