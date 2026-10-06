@props(['config', 'height' => 'h-72'])
{{-- Diagramm (Chart.js). $config: ['type' => 'bar', 'labels' => [...], 'datasets' => [['label' => ..., 'data' => [...]]], 'euro' => bool] --}}
@once
    @push('scripts')
        @vite('resources/js/charts.js')
    @endpush
@endonce
<div {{ $attributes->class(['relative', $height]) }}>
    <canvas data-chart='@json($config)' role="img" aria-label="{{ $attributes->get('aria-label', 'Diagramm') }}"></canvas>
</div>
