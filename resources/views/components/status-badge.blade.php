{{-- resources/views/components/status-badge.blade.php — badge status: teks + warna (WCAG) --}}
@props(['status'])
<span {{ $attributes->merge(['class' => 'badge '.$status->badgeClass()]) }}>{{ $status->label() }}</span>
