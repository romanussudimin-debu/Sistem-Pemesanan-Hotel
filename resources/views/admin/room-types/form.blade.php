{{-- resources/views/admin/room-types/form.blade.php — form tipe kamar: info, foto, fasilitas (FR-23, FR-25) --}}
<x-admin-layout :title="$roomType->exists ? __('Edit Room Type') : __('Add Room Type')">
    <form method="POST" enctype="multipart/form-data" action="{{ $roomType->exists ? route('admin.room-types.update', $roomType) : route('admin.room-types.store') }}" class="card card-body max-w-3xl space-y-4">
        @csrf @if ($roomType->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="branch_id">{{ __('Branch') }} *</label><select id="branch_id" name="branch_id" class="input">@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $roomType->branch_id) == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
            <div><label class="label" for="name">{{ __('Name') }} *</label><input id="name" name="name" value="{{ old('name', $roomType->name) }}" class="input" required>@error('name')<p class="error">{{ $message }}</p>@enderror @error('slug')<p class="error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="label" for="capacity">{{ __('Capacity (guests)') }} *</label><input id="capacity" type="number" min="1" max="10" name="capacity" value="{{ old('capacity', $roomType->capacity) }}" class="input" required>@error('capacity')<p class="error">{{ $message }}</p>@enderror</div>
            <div><label class="label" for="size_sqm">{{ __('Size (m²)') }}</label><input id="size_sqm" type="number" name="size_sqm" value="{{ old('size_sqm', $roomType->size_sqm) }}" class="input"></div>
            <div><label class="label" for="base_price">{{ __('Base rate / night (Rp)') }} *</label><input id="base_price" type="number" min="0" step="1000" name="base_price" value="{{ old('base_price', $roomType->base_price ? (int) $roomType->base_price : '') }}" class="input" required>@error('base_price')<p class="error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="description_id">{{ __('Description (Indonesian)') }}</label><textarea id="description_id" name="description_id" rows="3" class="input">{{ old('description_id', $roomType->description_id) }}</textarea></div>
            <div><label class="label" for="description_en">{{ __('Description (English)') }}</label><textarea id="description_en" name="description_en" rows="3" class="input">{{ old('description_en', $roomType->description_en) }}</textarea></div>
        </div>
        <fieldset>
            <legend class="label">{{ __('Facilities') }}</legend>
            @php $selected = old('facilities', $roomType->exists ? $roomType->facilities->pluck('id')->all() : []); @endphp
            <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                @foreach ($facilities as $f)<label class="flex items-center gap-2"><input type="checkbox" name="facilities[]" value="{{ $f->id }}" @checked(in_array($f->id, $selected)) class="rounded text-brand-700"> {{ $f->name }}</label>@endforeach
            </div>
        </fieldset>
        <div>
            <label class="label" for="images">{{ __('Add photos (max 2 MB each)') }}</label>
            <input id="images" type="file" name="images[]" accept="image/*" multiple class="text-sm">@error('images.*')<p class="error">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $roomType->is_active)) class="rounded text-brand-700"> {{ __('Active (shown in search)') }}</label>
        <div class="flex justify-end gap-3"><a href="{{ route('admin.room-types.index') }}" class="btn-secondary">{{ __('Cancel') }}</a><button class="btn-primary">{{ __('Save') }}</button></div>
    </form>

    @if ($roomType->exists && $roomType->images->isNotEmpty())
        <div class="card card-body max-w-3xl">
            <h2 class="mb-3 font-semibold">{{ __('Photos') }}</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($roomType->images as $img)
                    <div><x-photo :path="$img->path" :label="''" class="h-24 w-full rounded-lg" /><x-admin.delete-button :action="route('admin.room-types.images.destroy', [$roomType, $img])" /></div>
                @endforeach
            </div>
        </div>
    @endif
</x-admin-layout>
