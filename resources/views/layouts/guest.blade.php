{{-- resources/views/layouts/guest.blade.php — halaman autentikasi (Breeze) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => ''])
</head>
<body class="bg-brand-50 bg-gradient-to-br from-brand-50 via-white to-accent-50 font-sans text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex w-full max-w-md items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-brand-800"><x-application-logo class="h-9 w-9" /> Nusa Stay</a>
            <x-lang-switch />
        </div>
        <div class="card w-full max-w-md p-6 sm:p-8">
            {{ $slot }}
        </div>
        <p class="mt-6 max-w-md text-center text-xs text-slate-500">{{ __('Demo accounts (password: password)') }}: admin@nusastay.test · budi@contoh.test</p>
    </div>
</body>
</html>
