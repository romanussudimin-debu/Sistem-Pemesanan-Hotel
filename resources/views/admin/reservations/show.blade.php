{{-- resources/views/admin/reservations/show.blade.php — A-03 Detail reservasi --}}
@php use App\Enums\ReservationStatus as S; @endphp
<x-admin-layout :title="$reservation->code">
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.reservations.index') }}" class="text-sm text-slate-500 hover:underline">← {{ __('Bookings') }}</a>
        <x-status-badge :status="$reservation->status" />
        <div class="ml-auto flex gap-2">
            @if ($reservation->status === S::Paid)
                <form method="POST" action="{{ route('admin.reservations.check-in', $reservation) }}">@csrf<button class="btn-primary btn-sm">{{ __('Check-in') }}</button></form>
            @elseif ($reservation->status === S::CheckedIn)
                <form method="POST" action="{{ route('admin.reservations.check-out', $reservation) }}">@csrf<button class="btn-primary btn-sm">{{ __('Check-out') }}</button></form>
            @endif
            @if ($reservation->isCancellable())
                <button x-data @click="$dispatch('open-modal', 'admin-cancel')" class="btn-danger btn-sm">{{ __('Cancel booking') }}</button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body space-y-4 lg:col-span-2">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">{{ __('Customer') }}</dt><dd class="font-medium">{{ $reservation->user->name }}</dd><dd class="text-xs text-slate-500">{{ $reservation->user->email }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Lead guest') }}</dt><dd class="font-medium">{{ $reservation->guest_name }}</dd><dd class="text-xs text-slate-500">{{ $reservation->guest_phone }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Branch') }}</dt><dd class="font-medium">{{ $reservation->branch->name }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Stay') }}</dt><dd class="font-medium">{{ date_local($reservation->check_in) }} → {{ date_local($reservation->check_out) }}</dd><dd class="text-xs text-slate-500">{{ trans_choice(':count night|:count nights', $reservation->nights, ['count' => $reservation->nights]) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Payment deadline') }}</dt><dd class="font-medium">{{ datetime_local($reservation->payment_deadline) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Total') }}</dt><dd class="font-medium">{{ money($reservation->total_amount) }}</dd>@if ($reservation->refund_amount > 0)<dd class="text-xs text-emerald-700">{{ __('Refund') }} {{ money($reservation->refund_amount) }} ({{ $reservation->refund_status->label() }})</dd>@endif</div>
            </dl>
            <table class="table">
                <thead><tr><th>{{ __('Room') }}</th><th>{{ __('No.') }}</th><th>{{ __('Guests') }}</th><th class="text-right">{{ __('Price') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@foreach ($reservation->rooms as $line)<tr><td>{{ $line->roomType->name }}</td><td>{{ $line->room->number }}</td><td>{{ $line->guests }}</td><td class="text-right">{{ money($line->line_total) }}</td></tr>@endforeach</tbody>
            </table>
            @if ($reservation->special_request)<p class="rounded-lg bg-slate-50 p-3 text-sm"><span class="text-slate-500">{{ __('Special requests') }}:</span> {{ $reservation->special_request }}</p>@endif
            @if ($reservation->cancel_reason)<p class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ __('Cancellation reason') }}: {{ $reservation->cancel_reason }} ({{ $reservation->canceller?->name ?? __('System') }})</p>@endif

            <h2 class="pt-2 font-semibold">{{ __('Payment attempts') }}</h2>
            <table class="table">
                <thead><tr><th>{{ __('Time') }}</th><th>{{ __('Method') }}</th><th>{{ __('Status') }}</th><th>{{ __('Amount') }}</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reservation->payments as $p)
                        <tr><td>{{ datetime_local($p->created_at) }}</td><td>{{ $p->method->label() }}</td><td>{{ $p->status->label() }}@if ($p->rejection_reason)<br><span class="text-xs text-red-600">{{ $p->rejection_reason }}</span>@endif</td><td>{{ money($p->amount) }}</td>
                            <td>@if ($p->proof_path)<a href="{{ route('admin.payments.show', $p) }}" class="link text-xs">{{ __('Receipt') }}</a>@endif</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-slate-500">{{ __('No payment attempts yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card card-body">
            <h2 class="mb-3 font-semibold">{{ __('Audit trail') }}</h2>
            <ol class="space-y-3 border-l-2 border-slate-200 pl-4 text-sm">
                @foreach ($reservation->statusLogs as $log)
                    <li><p class="font-medium">{{ $log->from_status?->label() ?? '—' }} → {{ $log->to_status->label() }}</p>
                        <p class="text-xs text-slate-500">{{ datetime_local($log->created_at) }} · {{ $log->actor?->name ?? __('System') }}</p>
                        @if ($log->note)<p class="text-xs text-slate-600">{{ $log->note }}</p>@endif</li>
                @endforeach
            </ol>
        </div>
    </div>

    @if ($reservation->isCancellable())
        <x-modal name="admin-cancel" focusable>
            <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}" class="space-y-4 p-6">
                @csrf
                <h2 class="text-lg font-semibold">{{ __('Cancel booking :code?', ['code' => $reservation->code]) }}</h2>
                <p class="text-sm">{{ __('Estimated refund') }}: <strong>{{ money($refundEstimate) }}</strong></p>
                <div><label class="label" for="reason">{{ __('Reason') }} *</label><input id="reason" name="reason" required maxlength="255" class="input" placeholder="{{ __('e.g. no-show') }}">@error('reason')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="flex justify-end gap-3"><button type="button" x-on:click="$dispatch('close')" class="btn-secondary">{{ __('No') }}</button><button class="btn-danger">{{ __('Yes, cancel') }}</button></div>
            </form>
        </x-modal>
    @endif
</x-admin-layout>
