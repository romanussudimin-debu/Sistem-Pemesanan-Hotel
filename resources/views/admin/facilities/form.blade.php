{{-- resources/views/admin/facilities/form.blade.php --}}
<x-admin-layout :title="$facility->exists ? __('Edit Facility') : __('Add Facility')">
    <form method="POST" action="{{ $facility->exists ? route('admin.facilities.update', $facility) : route('admin.facilities.store') }}" class="card card-body max-w-xl space-y-4">
        @csrf @if ($facility->exists) @method('PUT') @endif
        @foreach ([['name_id', __('Name (Indonesian)'), true], ['name_en', __('Name (English)'), true], ['icon', __('Icon key'), false]] as [$f, $l, $req])
            <div><label class="label" for="{{ $f }}">{{ $l }}{{ $req ? ' *' : '' }}</label><input id="{{ $f }}" name="{{ $f }}" value="{{ old($f, $facility->$f) }}" class="input" @if ($req) required @endif>@error($f)<p class="error">{{ $message }}</p>@enderror</div>
        @endforeach
        <div class="flex justify-end gap-3"><a href="{{ route('admin.facilities.index') }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-admin-layout>
