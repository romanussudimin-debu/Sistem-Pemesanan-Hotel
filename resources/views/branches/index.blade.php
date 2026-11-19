{{-- resources/views/branches/index.blade.php — P-04 Daftar cabang --}}
<x-app-layout :title="__('Branches')">
    <x-slot name="header"><h1 class="text-2xl font-bold">{{ __('Our Branches') }}</h1></x-slot>
    <div class="mx-auto grid max-w-6xl gap-6 px-4 py-6 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
        @foreach ($branches as $branch)
            <a href="{{ route('branches.show', $branch->slug) }}" class="card overflow-hidden transition hover:shadow-md">
                <x-photo :path="$branch->cover_image" :label="$branch->name" :seed="$branch->code" class="h-44 w-full" />
                <div class="card-body">
                    <h2 class="font-semibold">{{ $branch->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $branch->city }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $branch->description }}</p>
                    <p class="mt-3 text-sm">{{ __('from') }} <strong class="text-accent-600">{{ money($branch->min_price) }}</strong> / {{ __('night') }}</p>
                </div>
            </a>
        @endforeach
    </div>
</x-app-layout>
