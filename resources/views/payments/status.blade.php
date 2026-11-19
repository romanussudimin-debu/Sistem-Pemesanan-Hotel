{{-- resources/views/payments/status.blade.php — P-09 Status pembayaran --}}
@php use App\Enums\ReservationStatus as S; @endphp
<x-app-layout :title="__('Payment Status')">
    <div class="mx-auto max-w-2xl px-4 py-6 sm:px-6">
        <x-stepper :step="in_array($reservation->status, S::sold(), true) ? 4 : 3" />
        <div class="card card-body text-center">
            @switch($reservation->status)
                @case(S::Paid) @case(S::CheckedIn) @case(S::Completed)
                    <p class="text-5xl">✅</p>
                    <h1 class="mt-3 text-2xl font-bold">{{ __('Booking confirmed!') }}</h1>
                    <p class="mt-2 text-slate-600">{{ __('A confirmation email with your voucher has been sent.') }}</p>
                    @break
                @case(S::AwaitingVerification)
                    <p class="text-5xl">⏳</p>
                    <h1 class="mt-3 text-2xl font-bold">{{ __('Receipt received') }}</h1>
                    <p class="mt-2 text-slate-600">{{ __('Our team will verify your transfer within 24 hours. We will email you once it is confirmed.') }}</p>
                    @break
                @case(S::PendingPayment)
                    <p class="text-5xl">💳</p>
                    <h1 class="mt-3 text-2xl font-bold">{{ __('Awaiting payment') }}</h1>
                    @break
                @default
                    <p class="text-5xl">⛔</p>
                    <h1 class="mt-3 text-2xl font-bold">{{ $reservation->status->label() }}</h1>
            @endswitch

            <p class="mt-4 text-sm text-slate-500">{{ __('Booking code') }}: <strong class="font-mono text-slate-900">{{ $reservation->code }}</strong> · <x-status-badge :status="$reservation->status" /></p>

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @if ($reservation->status === S::PendingPayment && ! $reservation->isOverdue())
                    <a href="{{ route('payments.show', $reservation) }}" class="btn-primary">{{ __('Pay now') }}</a>
                @endif
                @can('voucher', $reservation)
                    <a href="{{ route('reservations.voucher', $reservation) }}" class="btn-primary">{{ __('Voucher') }}</a>
                @endcan
                <a href="{{ route('reservations.show', $reservation) }}" class="btn-secondary">{{ __('Booking details') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
