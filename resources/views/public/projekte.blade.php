<x-layouts.public title="Unsere Projekte">
    <h1 class="mb-2 text-3xl">Unsere Projekte</h1>
    @if ($event)<p class="mb-6 text-slate-600">Diese Projekte werden beim {{ $event->name }} unterstützt.</p>@endif
    @include('projects._liste', ['projekte' => $projekte])
    @if ($event)<div class="mt-6 max-w-2xl">@include('partials.verteilung-hinweis', ['event' => $event])</div>@endif
</x-layouts.public>
