<x-mail::message>
Hallo,

**{{ $einladung->einlader?->name ?? 'Ein Elternteil' }}** lädt Sie ein, **{{ $einladung->laeufer->name }}** beim Spendenlauf mitzuverwalten, z. B. als zweiter Elternteil. Danach sehen und bearbeiten Sie beide die Daten und Sponsoren des Läufers.

Melden Sie sich mit dieser E-Mail-Adresse ({{ $einladung->email }}) an und nehmen Sie die Einladung auf Ihrer Startseite an. Haben Sie noch kein Konto, registrieren Sie sich bitte zuerst mit dieser Adresse.

<x-mail::button :url="route('home')">
Einladung ansehen
</x-mail::button>

Die Einladung gilt {{ \App\Models\MitverwaltungEinladung::GUELTIG_TAGE }} Tage. Kennen Sie die Person nicht, ignorieren Sie diese E-Mail einfach.

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
