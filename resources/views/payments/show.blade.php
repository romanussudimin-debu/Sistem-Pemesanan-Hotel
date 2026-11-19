{{-- resources/views/payments/show.blade.php — P-07 Pembayaran (W-05, W-05b) --}}
<x-app-layout :title="__('Payment')">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6" x-data="{ method: '{{ old('sender_name') || $errors->any() ? 'transfer' : 'gateway' }}' }">
        <x-stepper :step="3" />

        <div class="card card-body mb-6" x-data="countdown('{{ $reservation->payment_deadline->toIso8601String() }}')">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-slate-500">{{ __('Booking code') }}: <strong class="font-mono text-slate-900">{{ $reservation->code }}</strong></p>
                <x-status-badge :status="$reservation->status" />
            </div>
            <p class="mt-2 text-sm text-slate-600">{{ $reservation->branch->name }} · {{ date_local($reservation->check_in) }} → {{ date_local($reservation->check_out) }}</p>
            <p class="mt-3 text-2xl font-bold">{{ money($reservation->total_amount) }}</p>
            <p class="mt-3 text-sm">{{ __('Complete payment within') }}
                <span class="ml-1 rounded bg-amber-100 px-2 py-1 font-mono font-bold text-amber-800" x-text="remaining" aria-live="polite"></span></p>
            <p class="mt-1 text-xs text-slate-500">{{ __('Deadline') }}: {{ datetime_local($reservation->payment_deadline) }}</p>
        </div>

        @if ($lastRejected)
            {{-- UC-09 A4: alasan penolakan + sisa kesempatan --}}
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">{{ __('Your previous receipt was REJECTED') }}: “{{ $lastRejected->rejection_reason }}”</p>
                <p>{{ __('Upload attempts left') }}: {{ $reservation->remainingRejections() + 1 }}</p>
            </div>
        @endif

        <h2 class="mb-3 font-semibold">{{ __('Choose payment method') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="card card-body cursor-pointer" :class="method === 'gateway' && 'ring-2 ring-brand-600'">
                <input type="radio" value="gateway" x-model="method" class="text-brand-700 focus:ring-brand-600">
                <span class="ml-2 font-semibold">{{ __('Payment Gateway (Simulated)') }}</span>
                <span class="mt-1 block text-sm text-slate-500">{{ __('Mock card / VA / e-wallet. Paid instantly.') }}</span>
            </label>
            <label class="card card-body cursor-pointer" :class="method === 'transfer' && 'ring-2 ring-brand-600'">
                <input type="radio" value="transfer" x-model="method" class="text-brand-700 focus:ring-brand-600">
                <span class="ml-2 font-semibold">{{ __('Manual Bank Transfer') }}</span>
                <span class="mt-1 block text-sm text-slate-500">{{ __('Upload your receipt. Verified by admin within 24 hours.') }}</span>
            </label>
        </div>

        <form x-show="method === 'gateway'" method="POST" action="{{ route('payments.gateway', $reservation) }}" class="mt-6" x-data="{ sending: false }" @submit="sending = true">
            @csrf
            <button class="btn-primary w-full sm:w-auto" :disabled="sending">{{ __('PAY NOW') }}</button>
        </form>

        <div x-show="method === 'transfer'" x-cloak class="mt-6 space-y-5">
            <div class="card card-body text-sm">
                <p class="font-semibold">{{ __('Transfer to') }}</p>
                <div class="mt-2 flex items-center justify-between gap-2" x-data="copyable('{{ $reservation->branch->bank_account_no }}')">
                    <span>{{ $reservation->branch->bank_name }} · <strong class="font-mono">{{ $reservation->branch->bank_account_no }}</strong> · {{ __('a/n') }} {{ $reservation->branch->bank_account_name }}</span>
                    <button type="button" @click="copy" class="btn-secondary btn-sm"><span x-text="copied ? '✓' : '{{ __('Copy') }}'"></span></button>
                </div>
                <div class="mt-2 flex items-center justify-between gap-2" x-data="copyable('{{ (int) $reservation->total_amount }}')">
                    <span>{{ __('EXACT amount') }}: <strong>{{ money($reservation->total_amount) }}</strong></span>
                    <button type="button" @click="copy" class="btn-secondary btn-sm"><span x-text="copied ? '✓' : '{{ __('Copy') }}'"></span></button>
                </div>
            </div>

            <form method="POST" action="{{ route('payments.proof', $reservation) }}" enctype="multipart/form-data" class="card card-body space-y-4" x-data="{ file: '', sending: false }" @submit="sending = true">
                @csrf
                <div>
                    <label for="proof" class="label">{{ __('Upload transfer receipt') }} *</label>
                    <label for="proof" class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 hover:border-brand-600">
                        <span class="text-2xl">↑</span>
                        <span x-text="file || '{{ __('Click to choose a file') }}'"></span>
                        <span class="text-xs">JPG, PNG, PDF · {{ __('max 2 MB') }}</span>
                    </label>
                    <input id="proof" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" required @change="file = $event.target.files[0]?.name">
                    @error('proof')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label class="label" for="sender_name">{{ __('Sender name') }} *</label><input id="sender_name" name="sender_name" value="{{ old('sender_name') }}" class="input" required>@error('sender_name')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="sender_bank">{{ __('Sender bank') }} *</label><input id="sender_bank" name="sender_bank" value="{{ old('sender_bank') }}" class="input" required>@error('sender_bank')<p class="error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="transfer_date">{{ __('Transfer date') }} *</label><input id="transfer_date" type="date" name="transfer_date" value="{{ old('transfer_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="input" required>@error('transfer_date')<p class="error">{{ $message }}</p>@enderror</div>
                </div>
                <button class="btn-primary" :disabled="sending">{{ __('SUBMIT RECEIPT') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
