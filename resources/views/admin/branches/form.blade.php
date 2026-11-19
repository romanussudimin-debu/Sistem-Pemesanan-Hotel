{{-- resources/views/admin/branches/form.blade.php — form tambah/ubah cabang (FR-22) --}}
<x-admin-layout :title="$branch->exists ? __('Edit Branch') : __('Add Branch')">
    <form method="POST" enctype="multipart/form-data" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="card card-body max-w-3xl space-y-4">
        @csrf @if ($branch->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['code', __('Code (3 letters)'), 'maxlength=3'], ['name', __('Name'), ''], ['city', __('City'), '']] as [$f, $l, $attr])
                <div><label class="label" for="{{ $f }}">{{ $l }} *</label><input id="{{ $f }}" name="{{ $f }}" value="{{ old($f, $branch->$f) }}" class="input" required {!! $attr !!}>@error($f)<p class="error">{{ $message }}</p>@enderror</div>
            @endforeach
        </div>
        <div><label class="label" for="address">{{ __('Address') }} *</label><input id="address" name="address" value="{{ old('address', $branch->address) }}" class="input" required>@error('address')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="phone">{{ __('Phone') }} *</label><input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="input" required>@error('phone')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $branch->email) }}" class="input">@error('email')<p class="error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="description_id">{{ __('Description (Indonesian)') }}</label><textarea id="description_id" name="description_id" rows="3" class="input">{{ old('description_id', $branch->description_id) }}</textarea></div>
            <div><label class="label" for="description_en">{{ __('Description (English)') }}</label><textarea id="description_en" name="description_en" rows="3" class="input">{{ old('description_en', $branch->description_en) }}</textarea></div>
        </div>
        <fieldset class="grid gap-4 rounded-lg border border-slate-200 p-4 sm:grid-cols-3">
            <legend class="px-1 text-sm font-semibold">{{ __('Bank account for transfers') }}</legend>
            @foreach ([['bank_name', __('Bank')], ['bank_account_no', __('Account number')], ['bank_account_name', __('Account holder')]] as [$f, $l])
                <div><label class="label" for="{{ $f }}">{{ $l }} *</label><input id="{{ $f }}" name="{{ $f }}" value="{{ old($f, $branch->$f) }}" class="input" required>@error($f)<p class="error">{{ $message }}</p>@enderror</div>
            @endforeach
        </fieldset>
        <div><label class="label" for="cover_image">{{ __('Cover photo') }}</label><input id="cover_image" type="file" name="cover_image" accept="image/*" class="text-sm">@error('cover_image')<p class="error">{{ $message }}</p>@enderror</div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active)) class="rounded text-brand-700"> {{ __('Active (shown in search)') }}</label>
        <div class="flex justify-end gap-3"><a href="{{ route('admin.branches.index') }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>
</x-admin-layout>
