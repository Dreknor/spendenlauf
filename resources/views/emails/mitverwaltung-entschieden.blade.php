<x-mail::message>
Hallo {{ $anfrage->user->vorname }},

@if ($anfrage->status === \App\Models\MitverwaltungAnfrage::ANGENOMMEN)
Ihre Anfrage wurde bestätigt: Sie können **{{ $anfrage->laeufer->name }}** jetzt mitverwalten.

<x-mail::button :url="route('laeufer.index')">
Meine Läufer
</x-mail::button>
@else
Ihre Anfrage zur Mitverwaltung von **{{ $anfrage->laeufer->name }}** wurde abgelehnt. Bei Fragen wenden Sie sich bitte an das Organisationsteam.
@endif

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
