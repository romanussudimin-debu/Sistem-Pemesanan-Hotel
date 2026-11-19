{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }" class="no-print sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold text-brand-800">
            <x-application-logo class="h-8 w-8" /> Nusa Stay
        </a>

        <div class="hidden items-center gap-6 text-sm font-medium md:flex">
            <a href="{{ route('branches.index') }}" class="hover:text-brand-700 {{ request()->routeIs('branches.*') ? 'text-brand-700' : '' }}">{{ __('Branches') }}</a>
            @auth
                <a href="{{ route('reservations.index') }}" class="hover:text-brand-700 {{ request()->routeIs('reservations.*', 'payments.*') ? 'text-brand-700' : '' }}">{{ __('My Bookings') }}</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-700">{{ __('Admin Panel') }}</a>
                @endif
            @endauth
            <x-lang-switch />
            @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1 rounded-lg px-2 py-1 hover:bg-slate-100">
                            {{ Str::of(auth()->user()->name)->before(' ') }}
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @else
                <a href="{{ route('login') }}" class="hover:text-brand-700">{{ __('Log in') }}</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm">{{ __('Register') }}</a>
            @endauth
        </div>

        <div class="flex items-center gap-3 md:hidden">
            <x-lang-switch />
            <button @click="open = !open" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Menu mobile (W-01m) --}}
    <div x-show="open" x-cloak class="space-y-1 border-t border-slate-200 px-4 py-3 text-sm md:hidden">
        <a href="{{ route('branches.index') }}" class="block rounded px-2 py-2 hover:bg-slate-100">{{ __('Branches') }}</a>
        @auth
            <a href="{{ route('reservations.index') }}" class="block rounded px-2 py-2 hover:bg-slate-100">{{ __('My Bookings') }}</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="block rounded px-2 py-2 hover:bg-slate-100">{{ __('Admin Panel') }}</a>
            @endif
            <a href="{{ route('profile.edit') }}" class="block rounded px-2 py-2 hover:bg-slate-100">{{ __('Profile') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded px-2 py-2 text-left hover:bg-slate-100">{{ __('Log Out') }}</button></form>
        @else
            <a href="{{ route('login') }}" class="block rounded px-2 py-2 hover:bg-slate-100">{{ __('Log in') }}</a>
            <a href="{{ route('register') }}" class="block rounded px-2 py-2 hover:bg-slate-100">{{ __('Register') }}</a>
        @endauth
    </div>
</nav>
