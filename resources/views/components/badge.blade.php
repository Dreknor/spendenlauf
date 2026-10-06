@props(['color' => 'gray'])
@php
    $farben = [
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'blue' => 'bg-navy-50 text-navy-700 ring-navy-200',
        'esz' => 'bg-esz-50 text-esz-700 ring-esz-200',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap', $farben[$color] ?? $farben['gray']]) }}>{{ $slot }}</span>
