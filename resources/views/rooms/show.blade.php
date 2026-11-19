{{-- resources/views/rooms/show.blade.php — P-03 Detail tipe kamar (W-03) --}}
<x-app-layout :title="$roomType->name">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <nav class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a> ›
            <a href="{{ route('branches.show', $branch->slug) }}" class="hover:underline">{{ $branch->name }}</a> › {{ $roomType->name }}
        </nav>

        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2" x-data="{ active: 0 }">
                @if ($roomType->images->isNotEmpty())
                    @foreach ($roomType->images as $i => $img)
                        <x-photo x-show="active === {{ $i }}" :path="$img->path" :label="$roomType->name" class="h-80 w-full rounded-xl" />
                    @endforeach
                    <div class="mt-2 flex gap-2">
                        @foreach ($roomType->images as $i => $img)
                            <button @click="active = {{ $i }}" class="h-16 w-24 overflow-hidden rounded-lg ring-brand-600" :class="active === {{ $i }} && 'ring-2'"><x-photo :path="$img->path" :label="''" class="h-full w-full" /></button>
                        @endforeach
                    </div>
                @else
                    <x-photo :label="$roomType->name" :seed="$roomType->id" class="h-80 w-full rounded-xl" />
                @endif

                <h2 class="mt-8 text-lg font-semibold">{{ __('Description') }}</h2>
                <p class="mt-2 text-slate-700">{{ $roomType->description }}</p>

                <h2 class="mt-6 text-lg font-semibold">{{ __('Facilities') }}</h2>
                <ul class="mt-2 grid grid-cols-2 gap-2 text-sm text-slate-700 sm:grid-cols-3">
                    @foreach ($roomType->facilities as $f)<li>✓ {{ $f->name }}</li>@endforeach
                </ul>

                <h2 class="mt-6 text-lg font-semibold">{{ __('Policies') }}</h2>
                <ul class="mt-2 space-y-1 text-sm text-slate-700">
                    <li>{{ __('Check-in from :in, check-out by :out (WITA).', ['in' => config('hotel.check_in_time'), 'out' => config('hotel.check_out_time')]) }}</li>
                    <li>{{ __('Cancellation: ≥ 7 days before check-in 100% refund · 3–6 days 50% · < 3 days no refund.') }}</li>
                    <li>{{ __('Please bring your booking code and an official ID (KTP/passport).') }}</li>
                </ul>
            </div>

            {{-- Panel pemesanan sticky (W-03) --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="card card-body space-y-4">
                    <div>
                        <h1 class="text-xl font-bold">{{ $roomType->name }}</h1>
                        <p class="text-sm text-slate-500">{{ $branch->name }} · {{ __('max :count guests', ['count' => $roomType->capacity]) }} · {{ $roomType->size_sqm }} m²</p>
                    </div>
                    <form method="GET" action="{{ route('rooms.show', [$branch->slug, $roomType->slug]) }}" class="grid grid-cols-2 gap-2 border-y border-slate-200 py-4">
                        <div><label class="label" for="ci">{{ __('Check-in') }}</label><input id="ci" type="date" name="check_in" value="{{ $checkIn->toDateString() }}" min="{{ today()->toDateString() }}" class="input"></div>
                        <div><label class="label" for="co">{{ __('Check-out') }}</label><input id="co" type="date" name="check_out" value="{{ $checkOut->toDateString() }}" class="input"></div>
                        <div><label class="label" for="rm">{{ __('Rooms') }}</label><input id="rm" type="number" name="rooms" min="1" max="{{ min(config('hotel.booking.max_rooms'), max(1, $available)) }}" value="{{ $rooms }}" class="input"></div>
                        <div><label class="label" for="gs">{{ __('Guests') }}</label><input id="gs" type="number" name="guests" min="1" max="{{ $roomType->capacity * $rooms }}" value="{{ $guests }}" class="input"></div>
                        <button class="btn-secondary col-span-2">{{ __('Update price') }}</button>
                    </form>

                    <dl class="space-y-1 text-sm">
                        @foreach ($quote->nights as $night)
                            <div class="flex justify-between"><dt>{{ date_local(\Carbon\CarbonImmutable::parse($night['date']), true) }}@if ($night['source'] === 'seasonal') <span class="badge bg-accent-100 text-accent-800">{{ $night['label'] }}</span>@endif</dt><dd>{{ money($night['price']) }}</dd></div>
                        @endforeach
                        @if ($rooms > 1)<div class="flex justify-between text-slate-500"><dt>× {{ $rooms }} {{ __('rooms') }}</dt><dd>{{ money($quote->subtotal) }}</dd></div>@endif
                        <div class="flex justify-between text-slate-500"><dt>{{ __('Tax & service :rate%', ['rate' => (int) $quote->taxRate]) }}</dt><dd>{{ money($quote->tax) }}</dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><dt>{{ __('Total') }}</dt><dd>{{ money($quote->total) }}</dd></div>
                    </dl>

                    @if ($available >= $rooms)
                        <a href="{{ route('checkout.create', ['room_type' => $roomType->id, 'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString(), 'rooms' => $rooms, 'guests' => $guests]) }}" class="btn-primary w-full">{{ __('Book Now') }}</a>
                        <p class="text-center text-xs text-slate-500">{{ trans_choice(':count room left for these dates|:count rooms left for these dates', $available, ['count' => $available]) }} · {{ __('Free cancellation until :date', ['date' => date_local($freeCancellationUntil)]) }}</p>
                    @else
                        <p class="rounded-lg bg-red-50 p-3 text-center text-sm font-semibold text-red-700">{{ __('Sold out for these dates') }}</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
