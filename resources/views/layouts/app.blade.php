{{-- resources/views/layouts/app.blade.php — layout publik & pelanggan (Tahap 4, 4.2) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => $title ?? ''])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    @include('layouts.navigation')

    <main class="flex-1">
        @isset($header)
            <div class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">{{ $header }}</div>
            </div>
        @endisset

        <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6"><x-flash /></div>

        {{ $slot }}
    </main>

    <footer class="no-print mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>&copy; {{ now()->year }} Nusa Stay Hotels — {{ __('fictional case study for a portfolio') }}</p>
            <p class="flex gap-4">
                <a href="{{ route('branches.index') }}" class="hover:text-brand-700">{{ __('Branches') }}</a>
                <span>{{ __('Check-in') }} {{ config('hotel.check_in_time') }} · {{ __('Check-out') }} {{ config('hotel.check_out_time') }} WITA</span>
            </p>
        </div>
    </footer>
</body>
</html>
