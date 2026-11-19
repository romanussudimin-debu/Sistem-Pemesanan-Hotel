{{-- resources/views/admin/room-rates/form.blade.php --}}
<x-admin-layout :title="$rate->exists ? __('Edit Rate') : __('Add Rate')">
    <form method="POST" action="{{ $rate->exists ? route('admin.room-rates.update', $rate) : route('admin.room-rates.store') }}" class="card card-body max-w-xl space-y-4">
        @csrf @if ($rate->exists) @method('PUT') @endif
        <div><label class="label" for="room_type_id">{{ __('Room type') }} *</label><select id="room_type_id" name="room_type_id" class="input">@foreach ($roomTypes as $t)<option value="{{ $t->id }}" @selected(old('room_type_id', $rate->room_type_id) == $t->id)>{{ $t->branch->code }} · {{ $t->name }} ({{ money($t->base_price) }})</option>@endforeach</select></div>
        <div><label class="label" for="label">{{ __('Label') }} *</label><input id="label" name="label" value="{{ old('label', $rate->label) }}" class="input" required placeholder="{{ __('e.g. Christmas & New Year') }}">@error('label')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="start_date">{{ __('Start date') }} *</label><input id="start_date" type="date" name="start_date" value="{{ old('start_date', $rate->start_date?->toDateString()) }}" class="input" required>@error('start_date')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="end_date">{{ __('End date') }} *</label><input id="end_date" type="date" name="end_date" value="{{ old('end_date', $rate->end_date?->toDateString()) }}" class="input" required>@error('end_date')<p class="error">{{ $message }}</p>@enderror</div>
        </div>
        <div><label class="label" for="price">{{ __('Rate / night (Rp)') }} *</label><input id="price" type="number" min="0" step="1000" name="price" value="{{ old('price', $rate->price ? (int) $rate->price : '') }}" class="input" required>@error('price')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="flex justify-end gap-3"><a href="{{ route('admin.room-rates.index') }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-admin-layout>
