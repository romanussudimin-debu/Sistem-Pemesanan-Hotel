{{-- resources/views/search/index.blade.php — P-02 Hasil pencarian (W-02, W-02e) --}}
<x-app-layout :title="__('Search Results')">
    <x-slot name="header">
        <x-search-form :branches="$branches" :values="$criteria" />
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        @php $available = $results->where('fits', true); @endphp
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">
                <strong class="text-slate-900">{{ trans_choice(':count room type available|:count room types available', $available->count(), ['count' => $available->count()]) }}</strong>
                {{ __('at') }} {{ $branch->name }} · {{ date_local(\Carbon\CarbonImmutable::parse($criteria['check_in'])) }} → {{ date_local(\Carbon\CarbonImmutable::parse($criteria['check_out'])) }}
                · {{ trans_choice(':count night|:count nights', $criteria['nights'], ['count' => $criteria['nights']]) }}
            </p>
            <form method="GET" action="{{ route('search') }}" class="flex items-center gap-2 text-sm">
                @foreach (Arr::except($criteria, ['sort', 'nights']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <label for="sort" class="text-slate-500">{{ __('Sort by') }}</label>
                <select id="sort" name="sort" class="input w-auto py-1.5" onchange="this.form.submit()">
                    <option value="price_asc">{{ __('Lowest price') }}</option>
                    <option value="price_desc" @selected(($criteria['sort'] ?? '') === 'price_desc')>{{ __('Highest price') }}</option>
                    <option value="capacity" @selected(($criteria['sort'] ?? '') === 'capacity')>{{ __('Capacity') }}</option>
                </select>
            </form>
        </div>

        @if ($available->isEmpty())
            {{-- UC-05 A2: keadaan kosong + saran cabang lain --}}
            <div class="card card-body mb-6 text-center">
                <p class="text-lg font-semibold">{{ __('No rooms available at :branch for these dates.', ['branch' => $branch->name]) }}</p>
                @if ($alternatives->isNotEmpty())
                    <p class="mt-3 text-sm text-slate-600">{{ __('Available at other branches on the same dates:') }}</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($alternatives as $alt)
                            <li><a class="link" href="{{ route('search', ['branch' => $alt['branch']->slug] + Arr::except($criteria, ['branch', 'nights'])) }}">{{ $alt['branch']->name }} — {{ trans_choice(':count room type|:count room types', $alt['types'], ['count' => $alt['types']]) }} →</a></li>
                        @endforeach
                    </ul>
                @endif
                <p class="mt-3 text-sm text-slate-500">{{ __('Or try different dates above.') }}</p>
            </div>
        @endif

        <div class="space-y-4">
            @foreach ($results as $type)
                <article class="card flex flex-col overflow-hidden sm:flex-row {{ $type->fits ? '' : 'opacity-60' }}">
                    <x-photo :path="$type->images->first()?->path" :label="$type->name" :seed="$type->id" class="h-44 w-full sm:h-auto sm:w-64 sm:flex-none" />
                    <div class="flex flex-1 flex-col gap-3 p-5 sm:flex-row sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $type->name }}</h3>
                            <p class="text-sm text-slate-600">{{ __('max :count guests', ['count' => $type->capacity]) }} · {{ $type->size_sqm }} m²</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $type->facilities->take(4)->pluck('name')->join(' · ') }}@if ($type->facilities->count() > 4) · +{{ $type->facilities->count() - 4 }}@endif</p>
                            @if ($type->fits && $type->available_rooms <= 3)
                                <p class="mt-2 text-sm font-semibold text-accent-700">{{ trans_choice('Only :count room left|Only :count rooms left', $type->available_rooms, ['count' => $type->available_rooms]) }}</p>
                            @endif
                            @unless ($type->fits)
                                <p class="mt-2 text-sm font-semibold text-red-700">{{ $type->available_rooms < $criteria['rooms'] ? __('Sold out for these dates') : __('Not enough capacity for :count guests', ['count' => $criteria['guests']]) }}</p>
                            @endunless
                        </div>
                        <div class="flex flex-col items-start gap-2 sm:items-end sm:text-right">
                            <p><span class="text-xl font-bold text-slate-900">{{ money($type->quote->perRoom / $type->quote->nightCount()) }}</span> <span class="text-sm text-slate-500">/ {{ __('night') }}</span></p>
                            <p class="text-sm text-slate-600">{{ __('Total') }}: <strong>{{ money($type->quote->total) }}</strong>*</p>
                            <div class="mt-auto flex gap-2">
                                <a href="{{ route('rooms.show', [$branch->slug, $type->slug] + Arr::only($criteria, ['check_in', 'check_out', 'guests', 'rooms'])) }}" class="btn-secondary btn-sm">{{ __('Details') }}</a>
                                @if ($type->fits)
                                    <a href="{{ route('checkout.create', ['room_type' => $type->id] + Arr::only($criteria, ['check_in', 'check_out', 'guests', 'rooms'])) }}" class="btn-primary btn-sm">{{ __('Book Now') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-slate-500">* {{ __('Includes :rate% tax & service. Average nightly rate; seasonal rates may apply.', ['rate' => (int) config('hotel.tax_rate')]) }}</p>
    </div>
</x-app-layout>
