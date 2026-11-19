{{-- resources/views/components/stat-card.blade.php — kartu KPI dashboard --}}
@props(['label', 'value', 'hint' => null, 'tone' => 'default', 'href' => null])
@php $toneClass = ['default' => 'border-slate-200', 'warning' => 'border-amber-300 bg-amber-50', 'danger' => 'border-red-300 bg-red-50'][$tone]; @endphp
<div class="card border {{ $toneClass }} p-4">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</p>
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{!! $hint !!}</p>@endif
    @if ($href)<a href="{{ $href }}" class="link mt-2 inline-block text-xs">{{ __('Review') }} →</a>@endif
</div>
