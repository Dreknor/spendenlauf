<x-mail::message>
Hallo {{ $anfrage->laeufer->besitzer->vorname }},

**{{ $anfrage->user->name }}** ({{ $anfrage->user->email }}) möchte **{{ $anfrage->laeufer->name }}** beim Spendenlauf mitverwalten, z. B. als zweiter Elternteil. Danach können Sie beide Daten und Sponsoren des Läufers sehen und bearbeiten.

Bitte bestätigen oder lehnen Sie die Anfrage auf Ihrer Startseite ab.

<x-mail::button :url="route('home')">
Anfrage ansehen
</x-mail::button>

Kennen Sie die Person nicht, lehnen Sie die Anfrage einfach ab.

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
