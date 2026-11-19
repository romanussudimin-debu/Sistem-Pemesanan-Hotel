{{-- resources/views/admin/room-types/index.blade.php — A-06 Kelola tipe kamar --}}
<x-admin-layout :title="__('Room Types')">
    <x-slot name="actions"><a href="{{ route('admin.room-types.create') }}" class="btn-primary btn-sm">+ {{ __('Add Room Type') }}</a></x-slot>
    <form method="GET" class="flex gap-2"><select name="branch_id" class="input w-auto" onchange="this.form.submit()"><option value="">{{ __('All branches') }}</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>@endforeach</select></form>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Capacity') }}</th><th>{{ __('Base rate') }}</th><th>{{ __('Rooms') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($roomTypes as $t)
                    <tr>
                        <td class="font-medium">{{ $t->name }}</td><td>{{ Str::after($t->branch->name, 'Nusa Stay ') }}</td><td>{{ $t->capacity }}</td><td>{{ money($t->base_price) }}</td><td>{{ $t->rooms_count }}</td>
                        <td><span class="badge {{ $t->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">{{ $t->is_active ? __('Active') : __('Inactive') }}</span></td>
                        <td class="space-x-3 text-right"><a href="{{ route('admin.room-rates.index', ['room_type_id' => $t->id]) }}" class="link">{{ __('Rates') }}</a><a href="{{ route('admin.room-types.edit', $t) }}" class="link">{{ __('Edit') }}</a><x-admin.delete-button :action="route('admin.room-types.destroy', $t)" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $roomTypes->links() }}
</x-admin-layout>
