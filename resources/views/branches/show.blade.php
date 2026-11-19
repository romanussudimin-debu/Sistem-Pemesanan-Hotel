{{-- resources/views/branches/show.blade.php — P-04 Detail cabang --}}
<x-app-layout :title="$branch->name">
    <x-photo :path="$branch->cover_image" :label="''" :seed="$branch->code" class="h-56 w-full" />
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <nav class="text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a> › {{ $branch->name }}</nav>
        <h1 class="mt-2 text-3xl font-bold">{{ $branch->name }}</h1>
        <p class="text-slate-500">{{ $branch->address }} · {{ $branch->phone }}</p>
        <p class="mt-4 max-w-3xl text-slate-700">{{ $branch->description }}</p>

        <div class="card card-body mt-6">
            <x-search-form :branches="collect([$branch])" :values="['branch' => $branch->slug, 'check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString()]" />
        </div>

        <h2 class="mt-10 text-xl font-bold">{{ __('Room Types') }}</h2>
        <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($branch->roomTypes as $type)
                <a href="{{ route('rooms.show', [$branch->slug, $type->slug]) }}" class="card overflow-hidden transition hover:shadow-md">
                    <x-photo :path="$type->images->first()?->path" :label="$type->name" :seed="$type->id" class="h-40 w-full" />
                    <div class="card-body">
                        <p class="text-sm text-slate-600">{{ __('max :count guests', ['count' => $type->capacity]) }} · {{ $type->size_sqm }} m²</p>
                        <p class="mt-2 font-semibold">{{ money($type->base_price) }} <span class="text-sm font-normal text-slate-500">/ {{ __('night') }}</span></p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
