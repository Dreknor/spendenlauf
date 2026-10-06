@props(['title' => null, 'subtitle' => null, 'padding' => true])
<section {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 sm:px-6">
            <div>
                @if ($title)<h2 class="text-base font-semibold">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="text-sm text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['p-4 sm:p-6' => $padding])>
        {{ $slot }}
    </div>
</section>
