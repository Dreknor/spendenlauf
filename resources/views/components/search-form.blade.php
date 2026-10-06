@props(['placeholder' => 'Suchen …'])
<form method="GET" class="flex flex-wrap items-end gap-2" {{ $attributes }}>
    <div class="relative w-full sm:w-72">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 mt-0.5 size-4 -translate-y-1/2 text-slate-400" />
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="pl-9" aria-label="Suche">
    </div>
    @foreach (request()->only(['sort', 'dir']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    {{ $slot }}
    <button type="submit" class="btn-light">Filtern</button>
    @if (request()->hasAny(['q', 'filter', 'kategorie', 'klasse', 'status', 'altersgruppe', 'geschlecht']))
        <a href="{{ url()->current() }}" class="btn-light">Zurücksetzen</a>
    @endif
</form>
