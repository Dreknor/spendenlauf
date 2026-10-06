@props(['href', 'icon', 'active' => false])
<a href="{{ $href }}" @class([
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium no-underline transition',
    'bg-white/15 text-white' => $active,
    'text-navy-100 hover:bg-white/10 hover:text-white' => ! $active,
])>
    <x-icon :name="$icon" class="size-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
