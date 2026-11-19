{{-- resources/views/admin/rooms/index.blade.php — A-07 Kelola kamar (W-08) --}}
<x-admin-layout :title="__('Rooms')">
    <x-slot name="actions"><a href="{{ route('admin.rooms.create') }}" class="btn-primary btn-sm">+ {{ __('Add Room') }}</a></x-slot>
    <form method="GET" class="card card-body grid gap-3 sm:grid-cols-5 sm:items-end">
        <div><label class="label" for="branch_id">{{ __('Branch') }}</label><select id="branch_id" name="branch_id" class="input"><option value="">{{ __('All') }}</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? '') == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="label" for="room_type_id">{{ __('Room type') }}</label><select id="room_type_id" name="room_type_id" class="input"><option value="">{{ __('All') }}</option>@foreach ($roomTypes as $t)<option value="{{ $t->id }}" @selected(($filters['room_type_id'] ?? '') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
        <div><label class="label" for="status">{{ __('Status') }}</label><select id="status" name="status" class="input"><option value="">{{ __('All') }}</option>@foreach (App\Enums\RoomStatus::cases() as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        <div><label class="label" for="q">{{ __('Room no.') }}</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input"></div>
        <button class="btn-primary">{{ __('Filter') }}</button>
    </form>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('No.') }}</th><th>{{ __('Floor') }}</th><th>{{ __('Type') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rooms as $room)
                    <tr>
                        <td class="font-semibold">{{ $room->number }}</td><td>{{ $room->floor }}</td><td>{{ $room->roomType->name }}</td><td>{{ $room->roomType->branch->code }}</td>
                        <td><span class="badge {{ $room->status->badgeClass() }}">{{ $room->status->label() }}</span></td>
                        <td class="space-x-3 text-right"><a href="{{ route('admin.rooms.edit', $room) }}" class="link">{{ __('Edit') }}</a><x-admin.delete-button :action="route('admin.rooms.destroy', $room)" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">{{ __('No rooms match the filter.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rooms->links() }}
</x-admin-layout>
