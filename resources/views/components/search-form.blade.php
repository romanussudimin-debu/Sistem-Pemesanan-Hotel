{{-- resources/views/components/search-form.blade.php — form pencarian kamar (W-01/W-02) --}}
@props(['branches', 'values' => []])
<form method="GET" action="{{ route('search') }}" {{ $attributes->merge(['class' => 'grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end']) }}>
    <div class="lg:col-span-2">
        <label for="branch" class="label">{{ __('Branch') }}</label>
        <select id="branch" name="branch" class="input" required>
            @foreach ($branches as $b)
                <option value="{{ $b->slug }}" @selected(($values['branch'] ?? '') === $b->slug)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="check_in" class="label">{{ __('Check-in') }}</label>
        {{-- BR-01: tanggal lampau & > 365 hari dinonaktifkan di date picker --}}
        <input id="check_in" type="date" name="check_in" class="input" required value="{{ $values['check_in'] ?? '' }}"
               min="{{ today()->toDateString() }}" max="{{ today()->addDays(config('hotel.booking.max_days_ahead'))->toDateString() }}">
    </div>
    <div>
        <label for="check_out" class="label">{{ __('Check-out') }}</label>
        <input id="check_out" type="date" name="check_out" class="input" required value="{{ $values['check_out'] ?? '' }}" min="{{ today()->addDay()->toDateString() }}">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="guests" class="label">{{ __('Guests') }}</label>
            <input id="guests" type="number" name="guests" min="1" max="20" class="input" required value="{{ $values['guests'] ?? 2 }}">
        </div>
        <div>
            <label for="rooms" class="label">{{ __('Rooms') }}</label>
            <input id="rooms" type="number" name="rooms" min="1" max="{{ config('hotel.booking.max_rooms') }}" class="input" required value="{{ $values['rooms'] ?? 1 }}">
        </div>
    </div>
    <button type="submit" class="btn-primary w-full">{{ __('Search') }}</button>
</form>
