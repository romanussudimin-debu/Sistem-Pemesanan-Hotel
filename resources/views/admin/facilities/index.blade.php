{{-- resources/views/admin/facilities/index.blade.php — A-08 Kelola fasilitas --}}
<x-admin-layout :title="__('Facilities')">
    <x-slot name="actions"><a href="{{ route('admin.facilities.create') }}" class="btn-primary btn-sm">+ {{ __('Add Facility') }}</a></x-slot>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Name (Indonesian)') }}</th><th>{{ __('Name (English)') }}</th><th>{{ __('Icon') }}</th><th>{{ __('Used by') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($facilities as $f)
                    <tr><td>{{ $f->name_id }}</td><td>{{ $f->name_en }}</td><td class="font-mono text-xs">{{ $f->icon }}</td><td>{{ trans_choice(':count room type|:count room types', $f->room_types_count, ['count' => $f->room_types_count]) }}</td>
                        <td class="space-x-3 text-right"><a href="{{ route('admin.facilities.edit', $f) }}" class="link">{{ __('Edit') }}</a><x-admin.delete-button :action="route('admin.facilities.destroy', $f)" /></td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
