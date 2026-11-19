{{-- resources/views/components/admin/chart.blade.php — kanvas Chart.js; data dikirim controller sebagai JSON --}}
@props(['config', 'label', 'height' => 'h-64'])
<div class="relative {{ $height }}">
    <canvas role="img" aria-label="{{ $label }}" x-data x-init="renderChart($el, {{ Js::from($config) }})"></canvas>
</div>
