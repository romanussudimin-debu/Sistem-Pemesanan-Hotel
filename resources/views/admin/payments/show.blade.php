{{-- resources/views/admin/payments/show.blade.php — W-10 Detail verifikasi pembayaran --}}
@php
    use App\Enums\PaymentStatus as P;
    $r = $payment->reservation;
    $isPdf = Str::endsWith(strtolower((string) $payment->proof_path), '.pdf');
    $lastChance = $r->remainingRejections() === 0;
@endphp
<x-admin-layout :title="__('Payment Verification').' · '.$r->code">
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <a href="{{ route('admin.payments.index') }}" class="text-slate-500 hover:underline">← {{ __('Queue') }}</a>
        <span class="badge bg-slate-100 text-slate-700">{{ $payment->status->label() }}</span>
        @if ($payment->status === P::Submitted)<span class="{{ $payment->created_at->lt(now()->subDay()) ? 'font-semibold text-red-600' : 'text-slate-500' }}">{{ __('Waiting') }} {{ $payment->created_at->diffForHumans(null, true) }}</span>@endif
        <span class="text-slate-500">· {{ __('Upload :n of max. 3', ['n' => $attempt]) }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card card-body">
            <h2 class="mb-3 font-semibold">{{ __('Transfer receipt') }}</h2>
            @if ($isPdf)
                <iframe src="{{ route('admin.payments.proof', $payment) }}" class="h-96 w-full rounded-lg border" title="{{ __('Transfer receipt') }}"></iframe>
            @else
                <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank"><img src="{{ route('admin.payments.proof', $payment) }}" alt="{{ __('Transfer receipt') }}" class="max-h-96 w-full rounded-lg border object-contain"></a>
            @endif
            <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank" class="link mt-2 inline-block text-sm">{{ __('Open full size') }} ↗</a>
        </div>

        <div class="card card-body space-y-4" x-data="{ c1: false, c2: false, c3: false }">
            <h2 class="font-semibold">{{ __('Billing details') }}</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-500">{{ __('Booking') }}</dt><dd><a href="{{ route('admin.reservations.show', $r) }}" class="link font-mono">{{ $r->code }}</a></dd></div>
                <div><dt class="text-slate-500">{{ __('Customer') }}</dt><dd>{{ $r->user->name }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Destination account') }}</dt><dd>{{ $r->branch->bank_name }} {{ $r->branch->bank_account_no }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Amount due') }}</dt><dd class="text-lg font-bold">{{ money($payment->amount) }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Sender') }}</dt><dd>{{ $payment->sender_name }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Bank / date') }}</dt><dd>{{ $payment->sender_bank }} · {{ date_local($payment->transfer_date) }}</dd></div>
            </dl>

            @if ($payment->status === P::Submitted)
                {{-- Checklist: tombol Setujui aktif hanya bila semua dicentang --}}
                <fieldset class="space-y-2 border-t border-slate-200 pt-4 text-sm">
                    <legend class="mb-2 font-medium">{{ __('Verification checklist') }}</legend>
                    <label class="flex gap-2"><input type="checkbox" x-model="c1" class="rounded text-brand-700"> {{ __('Amount matches the invoice') }}</label>
                    <label class="flex gap-2"><input type="checkbox" x-model="c2" class="rounded text-brand-700"> {{ __('Destination account is correct') }}</label>
                    <label class="flex gap-2"><input type="checkbox" x-model="c3" class="rounded text-brand-700"> {{ __('Receipt is clearly readable') }}</label>
                </fieldset>
                <div class="flex justify-between gap-3">
                    <button x-data @click="$dispatch('open-modal', 'reject-payment')" class="btn-secondary text-red-700">{{ __('Reject…') }}</button>
                    <form method="POST" action="{{ route('admin.payments.approve', $payment) }}">@csrf<button class="btn-primary" :disabled="!(c1 && c2 && c3)">✓ {{ __('Approve') }}</button></form>
                </div>
            @else
                <p class="rounded-lg bg-slate-50 p-3 text-sm">{{ __('Processed by :name on :time.', ['name' => $payment->verifier?->name ?? __('System'), 'time' => datetime_local($payment->verified_at)]) }}
                    @if ($payment->rejection_reason)<br><span class="text-red-700">{{ __('Reason') }}: {{ $payment->rejection_reason }}</span>@endif</p>
            @endif
        </div>
    </div>

    @if ($payment->status === P::Submitted)
        <x-modal name="reject-payment" focusable>
            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="space-y-4 p-6" x-data="{ reason: '' }">
                @csrf
                <h2 class="text-lg font-semibold">{{ __('Reject payment') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ([__('Amount is short'), __('Receipt is unreadable'), __('Wrong destination account')] as $chip)
                        <button type="button" @click="reason = '{{ $chip }}'" class="rounded-full bg-slate-100 px-3 py-1 text-xs hover:bg-slate-200">{{ $chip }}</button>
                    @endforeach
                </div>
                <div><label class="label" for="reason">{{ __('Rejection reason') }} *</label><input id="reason" name="reason" x-model="reason" required maxlength="255" class="input"></div>
                <p class="text-sm {{ $lastChance ? 'font-semibold text-red-700' : 'text-slate-600' }}">
                    {{ $lastChance ? __('This is the 3rd rejection: the booking will be CANCELLED.') : __('Customer attempts left after this: :n', ['n' => $r->remainingRejections()]) }}
                </p>
                <div class="flex justify-end gap-3"><button type="button" x-on:click="$dispatch('close')" class="btn-secondary">{{ __('Cancel') }}</button><button class="{{ $lastChance ? 'btn-danger' : 'btn-primary' }}">{{ __('Reject') }}</button></div>
            </form>
        </x-modal>
    @endif
</x-admin-layout>
