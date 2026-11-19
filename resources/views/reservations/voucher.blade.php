{{-- resources/views/reservations/voucher.blade.php — P-12 Voucher siap cetak / simpan PDF (FR-18) --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>@include('layouts.partials.head', ['title' => __('Voucher').' '.$reservation->code])</head>
<body class="bg-slate-100 p-4 font-sans text-slate-800 sm:p-10">
    <div class="no-print mx-auto mb-4 flex max-w-2xl justify-between">
        <a href="{{ route('reservations.show', $reservation) }}" class="btn-secondary btn-sm">← {{ __('Back') }}</a>
        <button onclick="window.print()" class="btn-primary btn-sm">{{ __('Print / Save as PDF') }}</button>
    </div>
    <div class="mx-auto max-w-2xl rounded-xl border border-slate-300 bg-white p-8">
        <div class="flex items-start justify-between border-b border-dashed border-slate-300 pb-5">
            <div class="flex items-center gap-3"><x-application-logo class="h-12 w-12" /><div><p class="text-xl font-bold">Nusa Stay Hotels</p><p class="text-sm text-slate-500">{{ __('Booking Voucher') }}</p></div></div>
            <div class="text-right"><p class="text-xs uppercase text-slate-500">{{ __('Booking code') }}</p><p class="font-mono text-lg font-bold">{{ $reservation->code }}</p><x-status-badge :status="$reservation->status" /></div>
        </div>
        <dl class="grid grid-cols-2 gap-4 py-5 text-sm">
            <div><dt class="text-slate-500">{{ __('Hotel') }}</dt><dd class="font-semibold">{{ $reservation->branch->name }}</dd><dd>{{ $reservation->branch->address }}</dd><dd>{{ $reservation->branch->phone }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Lead guest') }}</dt><dd class="font-semibold">{{ $reservation->guest_name }}</dd><dd>{{ $reservation->guest_phone }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Check-in') }}</dt><dd class="font-semibold">{{ date_local($reservation->check_in, true) }}, {{ config('hotel.check_in_time') }} WITA</dd></div>
            <div><dt class="text-slate-500">{{ __('Check-out') }}</dt><dd class="font-semibold">{{ date_local($reservation->check_out, true) }}, {{ config('hotel.check_out_time') }} WITA</dd></div>
        </dl>
        <table class="table">
            <thead><tr><th>{{ __('Room') }}</th><th>{{ __('No.') }}</th><th>{{ __('Guests') }}</th></tr></thead>
            <tbody>@foreach ($reservation->rooms as $line)<tr><td>{{ $line->roomType->name }}</td><td>{{ $line->room->number }}</td><td>{{ $line->guests }}</td></tr>@endforeach</tbody>
        </table>
        <p class="mt-5 flex justify-between border-t border-slate-200 pt-3 font-semibold"><span>{{ __('Total paid') }}</span><span>{{ money($reservation->total_amount) }}</span></p>
        <p class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ __('Please show this voucher and an official ID (KTP/passport) at check-in.') }}</p>
    </div>
</body>
</html>
