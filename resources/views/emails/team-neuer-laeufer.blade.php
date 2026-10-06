<x-mail::message>
Hallo {{ $name }},

**{{ $laeufer }}** wurde deinem Team **{{ $team }}** hinzugefügt.

<x-mail::button :url="route('teams.index')">
Teams ansehen
</x-mail::button>

Viele Grüße<br>
{{ config('app.name') }}
</x-mail::message>
