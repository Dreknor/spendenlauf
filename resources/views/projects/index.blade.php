<x-layouts.app title="Projekte">
    <x-page-header title="Projekte" :subtitle="$event ? 'Diese Projekte werden beim '.$event->name.' unterstützt.' : null">
        @can('edit projekt')
            <a href="{{ route('projects.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Projekt anlegen</a>
        @endcan
    </x-page-header>
    @include('projects._liste', ['projekte' => $projekte])
    @if ($event)
        <div class="mt-6 max-w-2xl">@include('partials.verteilung-hinweis', ['event' => $event])</div>
    @endif
    @if ($weitere->isNotEmpty())
        <h2 class="mt-10 mb-4 text-lg">Weitere Projekte (nicht im aktuellen Lauf)</h2>
        @include('projects._liste', ['projekte' => $weitere])
    @endif
</x-layouts.app>
