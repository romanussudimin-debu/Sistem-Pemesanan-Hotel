{{-- resources/views/home.blade.php — P-01 Beranda (W-01) --}}
<x-app-layout>
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-sky-600 text-white">
        <svg class="absolute inset-x-0 bottom-0 h-24 w-full text-slate-50" viewBox="0 0 1440 96" preserveAspectRatio="none" fill="currentColor" aria-hidden="true"><path d="M0 64 C240 16 480 16 720 48 S1200 96 1440 40 V96 H0z"/></svg>
        <div class="relative mx-auto max-w-6xl px-4 pb-32 pt-16 sm:px-6">
            <p class="text-sm font-semibold uppercase tracking-widest text-brand-100">Jimbaran · Ubud · Seminyak</p>
            <h1 class="mt-2 max-w-2xl text-3xl font-bold leading-tight sm:text-5xl">{{ __('Stay in Bali: three moods, one booking.') }}</h1>
            <p class="mt-4 max-w-xl text-brand-50">{{ __('Check real-time availability across all our branches and get instant confirmation.') }}</p>
        </div>
    </section>

    <div class="relative z-10 mx-auto -mt-24 max-w-6xl px-4 sm:px-6">
        <div class="card p-5 shadow-lg">
            <x-search-form :branches="$branches" :values="$defaults + ['branch' => $branches->first()?->slug]" />
        </div>
    </div>

    <section class="mx-auto mt-12 max-w-6xl px-4 sm:px-6">
        <h2 class="text-2xl font-bold">{{ __('Choose Our Branch') }}</h2>
        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($branches as $branch)
                <a href="{{ route('branches.show', $branch->slug) }}" class="card group overflow-hidden transition hover:-translate-y-1 hover:shadow-md">
                    <x-photo :path="$branch->cover_image" :label="$branch->name" :seed="$branch->code" class="h-44 w-full" />
                    <div class="card-body">
                        <h3 class="font-semibold text-slate-900 group-hover:text-brand-700">{{ $branch->name }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $branch->description }}</p>
                        <p class="mt-3 text-sm">{{ __('from') }} <span class="font-bold text-accent-600">{{ money($branch->min_price) }}</span> / {{ __('night') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto mt-12 max-w-6xl px-4 sm:px-6">
        <h2 class="text-xl font-bold">{{ __('Why book direct?') }}</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-3">
            @foreach ([[__('Best official rates'), __('No middleman fees; seasonal rates shown transparently.')], [__('Instant confirmation'), __('Rooms are locked the moment you confirm — no double bookings.')], [__('Flexible cancellation'), __('Full refund up to 7 days before check-in.')]] as [$title, $text])
                <li class="card card-body"><p class="font-semibold text-brand-800">✓ {{ $title }}</p><p class="mt-1 text-sm text-slate-600">{{ $text }}</p></li>
            @endforeach
        </ul>
    </section>
</x-app-layout>
