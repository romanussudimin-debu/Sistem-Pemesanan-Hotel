{{-- resources/views/checkout/create.blade.php — P-06 Checkout / data tamu (W-04) --}}
<x-app-layout :title="__('Guest Details')">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <x-stepper :step="2" />

        <form method="POST" action="{{ route('checkout.store') }}" x-data="{ agree: {{ old('agree') ? 'true' : 'false' }}, sending: false }" @submit="sending = true" class="grid gap-6 lg:grid-cols-3">
            @csrf
            <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
            <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
            <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">

            <div class="card card-body space-y-5 lg:col-span-2">
                <h2 class="text-lg font-semibold">{{ __('Lead Guest Details') }}</h2>
                @if ($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ __('Please check the highlighted fields.') }}</div>@endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="guest_name" class="label">{{ __('Full name') }} *</label>
                        <input id="guest_name" name="guest_name" class="input @error('guest_name') border-red-500 @enderror" value="{{ old('guest_name', $user->name) }}" required maxlength="100">
                        @error('guest_name')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="guest_phone" class="label">{{ __('Phone number') }} *</label>
                        <input id="guest_phone" name="guest_phone" class="input @error('guest_phone') border-red-500 @enderror" value="{{ old('guest_phone', $user->phone) }}" required placeholder="+62 812 xxxx xxxx">
                        @error('guest_phone')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="text-xs text-slate-500">{{ __('Pre-filled from your profile. Change it if you are booking for someone else.') }}</p>

                @foreach ($guestsPerRoom as $i => $g)
                    <div>
                        <label for="guests_{{ $i }}" class="label">{{ __('Room :n', ['n' => $i + 1]) }} — {{ $roomType->name }}: {{ __('number of guests') }}</label>
                        {{-- BR-04: pilihan dibatasi kapasitas --}}
                        <select id="guests_{{ $i }}" name="guests[]" class="input sm:w-40">
                            @for ($n = 1; $n <= $roomType->capacity; $n++)<option value="{{ $n }}" @selected(old("guests.$i", $g) == $n)>{{ $n }}</option>@endfor
                        </select>
                    </div>
                @endforeach
                @error('guests')<p class="error">{{ $message }}</p>@enderror

                <div>
                    <label for="special_request" class="label">{{ __('Special requests (optional)') }}</label>
                    <textarea id="special_request" name="special_request" rows="3" maxlength="500" class="input" placeholder="{{ __('e.g. high floor if possible') }}">{{ old('special_request') }}</textarea>
                </div>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="agree" value="1" x-model="agree" class="mt-0.5 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                    <span>{{ __('I agree to the cancellation policy and stay terms.') }} *</span>
                </label>
                @error('agree')<p class="error">{{ $message }}</p>@enderror

                <div class="flex justify-between gap-3 pt-2">
                    <a href="{{ url()->previous() }}" class="btn-secondary">← {{ __('Back') }}</a>
                    {{-- Tombol dikunci setelah diklik untuk mencegah double submit (NFR-07) --}}
                    <button type="submit" class="btn-primary" :disabled="!agree || sending">
                        <span x-show="!sending">{{ __('CONFIRM BOOKING') }}</span>
                        <span x-show="sending" x-cloak>{{ __('Processing…') }}</span>
                    </button>
                </div>
            </div>

            <aside class="card h-fit overflow-hidden lg:sticky lg:top-24">
                <x-photo :path="$roomType->images->first()?->path" :label="$roomType->name" :seed="$roomType->id" class="h-32 w-full" />
                <div class="card-body space-y-3 text-sm">
                    <h2 class="font-semibold uppercase tracking-wide text-slate-500">{{ __('Booking Summary') }}</h2>
                    <p class="font-semibold">{{ $roomType->name }}<br><span class="font-normal text-slate-500">{{ $roomType->branch->name }}</span></p>
                    <p>{{ date_local($checkIn) }} → {{ date_local($checkOut) }} ({{ trans_choice(':count night|:count nights', $quote->nightCount(), ['count' => $quote->nightCount()]) }})<br>{{ trans_choice(':count room|:count rooms', $rooms, ['count' => $rooms]) }}</p>
                    <dl class="space-y-1 border-t border-slate-200 pt-3">
                        <div class="flex justify-between"><dt>{{ __('Subtotal') }}</dt><dd>{{ money($quote->subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt>{{ __('Tax & service :rate%', ['rate' => (int) $quote->taxRate]) }}</dt><dd>{{ money($quote->tax) }}</dd></div>
                        <div class="flex justify-between text-base font-bold"><dt>{{ __('Total') }}</dt><dd>{{ money($quote->total) }}</dd></div>
                    </dl>
                    <p class="rounded-lg bg-amber-50 p-3 text-amber-800">⏱ {{ __('Pay within :hours hours after confirming.', ['hours' => config('hotel.payment.deadline_hours')]) }}</p>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>
