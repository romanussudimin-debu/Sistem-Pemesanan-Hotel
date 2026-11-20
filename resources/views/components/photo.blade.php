{{-- resources/views/components/photo.blade.php — foto dari storage, atau placeholder gradasi bila belum ada foto --}}
@props(['path' => null, 'label' => '', 'seed' => 0])
@php
    // Warna padat pertama = cadangan bila browser tidak menggambar gradasi
    $gradients = ['bg-brand-600 from-brand-600 to-sky-400', 'bg-emerald-600 from-emerald-600 to-lime-400', 'bg-amber-500 from-amber-500 to-rose-400', 'bg-indigo-600 from-indigo-600 to-cyan-400'];
    $gradient = $gradients[abs(crc32((string) $seed)) % count($gradients)];
@endphp
@if ($path)
    <img src="{{ Storage::disk('public')->url($path) }}" alt="{{ $label }}" {{ $attributes->merge(['class' => 'object-cover']) }} loading="lazy">
@else
    <div role="img" aria-label="{{ $label }}" {{ $attributes->merge(['class' => "relative flex items-end overflow-hidden bg-gradient-to-br {$gradient}"]) }}>
        <svg class="absolute inset-x-0 bottom-0 h-1/2 w-full text-white/20" viewBox="0 0 200 60" preserveAspectRatio="none" fill="currentColor"><path d="M0 40 Q 25 20 50 40 T 100 40 T 150 40 T 200 40 V60 H0z"/><path d="M0 50 Q 25 35 50 50 T 100 50 T 150 50 T 200 50 V60 H0z"/></svg>
        <span class="relative p-3 text-sm font-semibold text-white drop-shadow">{{ $label }}</span>
    </div>
@endif
