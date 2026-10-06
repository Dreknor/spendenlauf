@props(['label', 'value', 'hint' => null, 'icon' => null, 'accent' => false])
<div {{ $attributes->class(['card p-4 sm:p-5', 'bg-esz-600! ring-esz-600! text-white' => $accent]) }}>
    <div class="flex items-center justify-between gap-2">
        <p @class(['text-sm font-medium', 'text-esz-100' => $accent, 'text-slate-500' => ! $accent])>{{ $label }}</p>
        @if ($icon)<x-icon :name="$icon" @class(['size-5', 'text-esz-100' => $accent, 'text-esz-500' => ! $accent]) />@endif
    </div>
    <p @class(['mt-1 whitespace-nowrap text-xl font-bold tabular-nums xl:text-2xl', 'text-navy-800' => ! $accent])>{{ $value }}</p>
    @if ($hint)<p @class(['mt-1 text-xs', 'text-esz-100' => $accent, 'text-slate-500' => ! $accent])>{{ $hint }}</p>@endif
</div>
