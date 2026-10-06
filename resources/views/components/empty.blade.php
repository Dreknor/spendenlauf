@props(['message' => 'Noch keine Einträge vorhanden.'])
<div {{ $attributes->class(['rounded-xl border-2 border-dashed border-slate-200 p-8 text-center text-sm text-slate-500']) }}>
    <p>{{ $message }}</p>
    @if ($slot->isNotEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
