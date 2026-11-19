{{-- resources/views/admin/rooms/form.blade.php — form kamar (W-08b) --}}
<x-admin-layout :title="$room->exists ? __('Edit Room') : __('Add Room')">
    <form method="POST" action="{{ $room->exists ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" class="card card-body max-w-2xl space-y-4">
        @csrf @if ($room->exists) @method('PUT') @endif
        <div><label class="label" for="room_type_id">{{ __('Room type') }} *</label>
            <select id="room_type_id" name="room_type_id" class="input">
                @foreach ($roomTypes->groupBy('branch.name') as $branch => $types)
                    <optgroup label="{{ $branch }}">@foreach ($types as $t)<option value="{{ $t->id }}" @selected(old('room_type_id', $room->room_type_id) == $t->id)>{{ $t->name }}</option>@endforeach</optgroup>
                @endforeach
            </select>@error('room_type_id')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="number">{{ __('Room number') }} *</label><input id="number" name="number" value="{{ old('number', $room->number) }}" class="input" required maxlength="10">@error('number')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="floor">{{ __('Floor') }}</label><input id="floor" type="number" name="floor" value="{{ old('floor', $room->floor) }}" class="input"></div>
        </div>
        <fieldset><legend class="label">{{ __('Status') }} *</legend>
            <div class="flex flex-wrap gap-4 text-sm">@foreach (App\Enums\RoomStatus::cases() as $s)<label class="flex items-center gap-2"><input type="radio" name="status" value="{{ $s->value }}" @checked(old('status', $room->status?->value) === $s->value) class="text-brand-700"> {{ $s->label() }}</label>@endforeach</div>
        </fieldset>
        <div><label class="label" for="notes">{{ __('Notes') }}</label><input id="notes" name="notes" value="{{ old('notes', $room->notes) }}" class="input" maxlength="255"></div>
        @if ($upcomingBookings)
            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">⚠ {{ __('This room has :count upcoming booking(s). Changing its status does not cancel them; move guests via Bookings.', ['count' => $upcomingBookings]) }}</p>
        @endif
        <div class="flex justify-end gap-3"><a href="{{ route('admin.rooms.index') }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-admin-layout>
