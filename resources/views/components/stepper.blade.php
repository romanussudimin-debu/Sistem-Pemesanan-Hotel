{{-- resources/views/components/stepper.blade.php — indikator alur pemesanan 4 langkah (NFR-09) --}}
@props(['step' => 1])
@php $steps = [__('Choose Room'), __('Guest Details'), __('Payment'), __('Done')]; @endphp
<ol class="mb-6 flex items-center gap-2 text-xs font-medium sm:text-sm" aria-label="{{ __('Booking steps') }}">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; @endphp
        <li class="flex items-center gap-2 {{ $n <= $step ? 'text-brand-700' : 'text-slate-400' }}" @if ($n === $step) aria-current="step" @endif>
            <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs {{ $n < $step ? 'bg-brand-700 text-white' : ($n === $step ? 'border-2 border-brand-700' : 'border border-slate-300') }}">{{ $n < $step ? '✓' : $n }}</span>
            <span class="hidden sm:inline">{{ $label }}</span>
        </li>
        @if (! $loop->last)<li class="h-px flex-1 bg-slate-300" aria-hidden="true"></li>@endif
    @endforeach
</ol>
