{{-- resources/views/reservations/show.blade.php — P-11 Detail reservasi + modal pembatalan (W-06b) --}}
@php use App\Enums\ReservationStatus as S; @endphp
<x-app-layout :title="$reservation->code">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6">
        <a href="{{ route('reservations.index') }}" class="text-sm text-slate-500 hover:underline">← {{ __('My Bookings') }}</a>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-mono text-2xl font-bold">{{ $reservation->code }}</h1>
            <x-status-badge :status="$reservation->status" class="text-sm" />
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="card card-body space-y-4 md:col-span-2">
                <div>
                    <h2 class="font-semibold">{{ $reservation->branch->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $reservation->branch->address }}</p>
                </div>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-500">{{ __('Check-in') }}</dt><dd class="font-medium">{{ date_local($reservation->check_in, true) }}, {{ config('hotel.check_in_time') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Check-out') }}</dt><dd class="font-medium">{{ date_local($reservation->check_out, true) }}, {{ config('hotel.check_out_time') }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Lead guest') }}</dt><dd class="font-medium">{{ $reservation->guest_name }} · {{ $reservation->guest_phone }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Guests') }}</dt><dd class="font-medium">{{ $reservation->total_guests }}</dd></div>
                </dl>
                <table class="table">
                    <thead><tr><th>{{ __('Room') }}</th><th>{{ __('Guests') }}</th><th class="text-right">{{ __('Price') }}</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reservation->rooms as $line)
                            <tr><td>{{ $line->roomType->name }}@if (in_array($reservation->status, S::sold(), true)) <span class="text-slate-400">· {{ __('No.') }} {{ $line->room->number }}</span>@endif</td><td>{{ $line->guests }}</td><td class="text-right">{{ money($line->line_total) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <dl class="space-y-1 border-t border-slate-200 pt-3 text-sm">
                    <div class="flex justify-between"><dt>{{ __('Subtotal') }}</dt><dd>{{ money($reservation->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('Tax & service :rate%', ['rate' => (int) $reservation->tax_rate]) }}</dt><dd>{{ money($reservation->tax_amount) }}</dd></div>
                    <div class="flex justify-between text-base font-bold"><dt>{{ __('Total') }}</dt><dd>{{ money($reservation->total_amount) }}</dd></div>
                    @if ($reservation->status === S::Cancelled)
                        <div class="flex justify-between text-emerald-700"><dt>{{ __('Refund') }} ({{ $reservation->refund_status->label() }})</dt><dd>{{ money($reservation->refund_amount) }}</dd></div>
                    @endif
                </dl>
                @if ($reservation->special_request)<p class="rounded-lg bg-slate-50 p-3 text-sm"><span class="text-slate-500">{{ __('Special requests') }}:</span> {{ $reservation->special_request }}</p>@endif
            </div>

            <aside class="space-y-4">
                <div class="card card-body space-y-2">
                    @if ($reservation->status === S::PendingPayment)
                        <a href="{{ route('payments.show', $reservation) }}" class="btn-primary w-full">{{ __('Pay now') }}</a>
                        <p class="text-center text-xs text-amber-700">{{ __('Pay before :time', ['time' => datetime_local($reservation->payment_deadline)]) }}</p>
                    @endif
                    @can('voucher', $reservation)<a href="{{ route('reservations.voucher', $reservation) }}" class="btn-primary w-full">{{ __('Voucher') }}</a>@endcan
                    @can('cancel', $reservation)
                        <button x-data @click="$dispatch('open-modal', 'cancel-reservation')" class="btn-secondary w-full text-red-700">{{ __('Cancel booking') }}</button>
                    @endcan
                </div>

                <div class="card card-body">
                    <h2 class="mb-3 text-sm font-semibold">{{ __('History') }}</h2>
                    <ol class="space-y-3 border-l-2 border-slate-200 pl-4 text-sm">
                        @foreach ($reservation->statusLogs as $log)
                            <li><p class="font-medium">{{ $log->to_status->label() }}</p><p class="text-xs text-slate-500">{{ datetime_local($log->created_at) }}</p></li>
                        @endforeach
                    </ol>
                </div>
            </aside>
        </div>
    </div>

    @can('cancel', $reservation)
        {{-- W-06b: tombol aman ("Tidak") menjadi fokus default --}}
        <x-modal name="cancel-reservation" focusable>
            <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="space-y-4 p-6">
                @csrf
                <h2 class="text-lg font-semibold">{{ __('Cancel booking :code?', ['code' => $reservation->code]) }}</h2>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">{{ __('Check-in') }}</dt><dd>{{ date_local($reservation->check_in) }} ({{ trans_choice(':count day away|:count days away', max(0, $refund['days']), ['count' => max(0, $refund['days'])]) }})</dd></div>
                    @if ($reservation->status === S::Paid)
                        <div class="flex justify-between"><dt class="text-slate-500">{{ __('Policy') }}</dt><dd>{{ __('refund :percent%', ['percent' => $refund['percent']]) }}</dd></div>
                    @endif
                    <div class="flex justify-between font-semibold"><dt>{{ __('Estimated refund') }}</dt><dd>{{ money($refund['amount']) }}</dd></div>
                </dl>
                <div><label class="label" for="reason">{{ __('Reason (optional)') }}</label><input id="reason" name="reason" maxlength="255" class="input"></div>
                @if ($refund['amount'] > 0)<p class="text-xs text-slate-500">{{ __('Refunds are processed manually by our team within 7 business days.') }}</p>@endif
                <div class="flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary">{{ __('No') }}</button>
                    <button class="btn-danger">{{ __('Yes, cancel') }}</button>
                </div>
            </form>
        </x-modal>
    @endcan
</x-app-layout>
