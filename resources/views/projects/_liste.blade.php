@if ($projekte->isEmpty())
    <x-empty message="Noch keine Projekte." />
@else
    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($projekte as $p)
            <article class="card overflow-hidden">
                @if ($url = $p->bildUrl())
                    <img src="{{ $url }}" alt="" class="h-48 w-full object-cover" loading="lazy">
                @endif
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-lg">{{ $p->name }}</h3>
                        @auth
                            @can('edit projekt')
                                <a href="{{ route('projects.edit', $p) }}" class="btn-light btn-sm"><x-icon name="pencil" class="size-4" /></a>
                            @endcan
                        @endauth
                    </div>
                    <div class="prose-sm mt-2 space-y-2 text-sm text-slate-600">{!! $p->beschreibungHtml() !!}</div>
                </div>
            </article>
        @endforeach
    </div>
@endif
