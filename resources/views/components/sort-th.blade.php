@props(['column', 'label', 'num' => false])
@php
    $aktiv = request('sort') === $column;
    $richtung = $aktiv && request('dir', 'asc') === 'asc' ? 'desc' : 'asc';
@endphp
<th @class(['num' => $num])>
    <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => $richtung, 'page' => null]) }}"
       class="inline-flex items-center gap-1 text-slate-500 hover:text-esz-600">
        {{ $label }}
        @if ($aktiv)<span aria-hidden="true">{{ request('dir', 'asc') === 'asc' ? '▲' : '▼' }}</span>@endif
    </a>
</th>
