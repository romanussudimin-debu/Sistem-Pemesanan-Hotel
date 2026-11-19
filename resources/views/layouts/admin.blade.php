{{-- resources/views/layouts/admin.blade.php — layout admin dengan sidebar (W-07) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ sidebar: false }">
    @php
        $menu = [
            [null, [['admin.dashboard', 'admin.dashboard', __('Dashboard')], ['admin.reservations.index', 'admin.reservations.*', __('Bookings')], ['admin.payments.index', 'admin.payments.*', __('Payment Verification'), $pendingVerifications]]],
            [__('Master Data'), [['admin.branches.index', 'admin.branches.*', __('Branches')], ['admin.room-types.index', 'admin.room-types.*', __('Room Types')], ['admin.rooms.index', 'admin.rooms.*', __('Rooms')], ['admin.facilities.index', 'admin.facilities.*', __('Facilities')], ['admin.room-rates.index', 'admin.room-rates.*', __('Seasonal Rates')]]],
            [__('Analytics'), [['admin.reports.index', 'admin.reports.*', __('Reports')]]],
        ];
    @endphp

    {{-- Sidebar: tetap di desktop, drawer di layar < 1024 px --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'" class="no-print fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-slate-900 text-slate-300 transition lg:translate-x-0">
        <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-2 px-5 font-bold text-white">
            <x-application-logo class="h-7 w-7" /> Nusa Stay <span class="rounded bg-brand-600 px-1.5 text-[10px] uppercase">Admin</span>
        </a>
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4 text-sm">
            @foreach ($menu as [$group, $items])
                <div>
                    @if ($group)<p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</p>@endif
                    @foreach ($items as $item)
                        <a href="{{ route($item[0]) }}" class="flex items-center justify-between rounded-lg px-3 py-2 {{ request()->routeIs($item[1]) ? 'bg-slate-800 font-semibold text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                            {{ $item[2] }}
                            @if (! empty($item[3]))<span class="rounded-full bg-red-500 px-2 text-xs font-bold text-white">{{ $item[3] }}</span>@endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="border-t border-slate-800 p-3 text-sm">
            <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800">{{ __('View website') }} ↗</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left hover:bg-slate-800">{{ __('Log Out') }}</button></form>
        </div>
    </aside>
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="lg:pl-60">
        <header class="no-print sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebar = true" class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-lg font-semibold">{{ $title }}</h1>
            </div>
            <div class="flex items-center gap-4 text-sm">
                {{ $actions ?? '' }}
                <x-lang-switch />
                <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <main class="space-y-6 p-4 sm:p-6">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</body>
</html>
