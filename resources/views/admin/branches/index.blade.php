{{-- resources/views/admin/branches/index.blade.php — A-05 Kelola cabang --}}
<x-admin-layout :title="__('Branches')">
    <x-slot name="actions"><a href="{{ route('admin.branches.create') }}" class="btn-primary btn-sm">+ {{ __('Add Branch') }}</a></x-slot>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('City') }}</th><th>{{ __('Room types') }}</th><th>{{ __('Rooms') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($branches as $b)
                    <tr>
                        <td class="font-mono">{{ $b->code }}</td><td class="font-medium">{{ $b->name }}</td><td>{{ $b->city }}</td>
                        <td>{{ $b->room_types_count }}</td><td>{{ $b->rooms_count }}</td>
                        <td><span class="badge {{ $b->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">{{ $b->is_active ? __('Active') : __('Inactive') }}</span></td>
                        <td class="space-x-3 text-right"><a href="{{ route('admin.branches.edit', $b) }}" class="link">{{ __('Edit') }}</a>@unless ($b->reservations_count)<x-admin.delete-button :action="route('admin.branches.destroy', $b)" />@endunless</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
