{{-- Filter für Läufer-Auswertungen. $filter aus AuswertungController::filterOptionen --}}
<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    @foreach (request()->only(['dim', 'ansicht', 'sort', 'dir']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <x-select name="altersgruppe" :options="$filter['altersgruppe']" :selected="request('altersgruppe')" placeholder="alle Altersgruppen" class="w-44" />
    <x-select name="geschlecht" :options="$filter['geschlecht']" :selected="request('geschlecht')" placeholder="alle Geschlechter" class="w-40" />
    <x-select name="kategorie" :options="$filter['kategorie']" :selected="request('kategorie')" placeholder="alle Kategorien" class="w-48" />
    @if ($filter['klasse'])
        <x-select name="stufe" :options="$filter['stufe']" :selected="request('stufe')" placeholder="alle Stufen" class="w-36" />
        <x-select name="klasse" :options="$filter['klasse']" :selected="request('klasse')" placeholder="alle Klassen" class="w-36" />
    @endif
    <x-select name="nur" :options="['teilnehmer' => 'nur mit Runden']" :selected="request('nur')" placeholder="alle Läufer" class="w-40" />
    <button type="submit" class="btn-light">Filtern</button>
    @if (request()->hasAny(['altersgruppe', 'geschlecht', 'kategorie', 'klasse', 'stufe', 'nur']))
        <a href="{{ url()->current().'?'.http_build_query(request()->only(['dim', 'ansicht'])) }}" class="btn-light">Zurücksetzen</a>
    @endif
</form>
