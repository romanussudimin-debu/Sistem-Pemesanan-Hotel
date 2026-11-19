{{-- resources/views/admin/dashboard.blade.php — A-01 Dashboard (W-07, FR-30) --}}
@php
    $revenue = $stats['revenue_by_branch'];
    $periods = $revenue->pluck('period')->unique()->sort()->values();
    $revenueChart = [
        'type' => 'bar',
        'data' => [
            'labels' => $periods->map(fn ($p) => \Carbon\Carbon::createFromFormat('Y-m', $p)->locale(app()->getLocale())->translatedFormat('M Y'))->all(),
            'datasets' => $revenue->groupBy('branch')->values()->map(fn ($rows, $i) => [
                'label' => $rows->first()->branch,
                'data' => $periods->map(fn ($p) => round(($rows->firstWhere('period', $p)?->net ?? 0) / 1e6, 1))->all(),
                'backgroundColor' => ['#0f766e', '#d97706', '#4f46e5'][$i % 3],
            ])->all(),
        ],
        'options' => ['maintainAspectRatio' => false, 'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'title' => ['display' => true, 'text' => __('Million Rp')]]]],
    ];
    $occChart = [
        'type' => 'line',
        'data' => [
            'labels' => collect($stats['occupancy_daily']['labels'])->map(fn ($d) => substr($d, 8, 2).'/'.substr($d, 5, 2))->all(),
            'datasets' => collect($stats['occupancy_daily']['series'])->values()->map(fn ($s, $i) => $s + ['borderColor' => ['#0f766e', '#d97706', '#4f46e5'][$i % 3], 'tension' => 0.3, 'pointRadius' => 0])->all(),
        ],
        'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['min' => 0, 'max' => 100]]],
    ];
    $change = fn ($v, $suffix = '%') => $v === null ? '' : ($v >= 0 ? '<span class="text-emerald-600">▲ '.$v.$suffix.'</span>' : '<span class="text-red-600">▼ '.abs($v).$suffix.'</span>');
@endphp
<x-admin-layout :title="__('Dashboard')">
    <x-slot name="actions">
        <form method="GET"><select name="branch_id" class="input py-1.5 text-sm" onchange="this.form.submit()" aria-label="{{ __('Branch') }}">
            <option value="">{{ __('All branches') }}</option>
            @foreach ($branches as $b)<option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->name }}</option>@endforeach
        </select></form>
    </x-slot>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card :label="__('Bookings today')" :value="$stats['today_bookings']" />
        <x-stat-card :label="__('Check-ins today')" :value="$stats['today_check_ins']" />
        <x-stat-card :label="__('Check-outs today')" :value="$stats['today_check_outs']" />
        <x-stat-card :label="__('Needs verification')" :value="$stats['pending_verifications']" :tone="$stats['overdue_verifications'] ? 'danger' : ($stats['pending_verifications'] ? 'warning' : 'default')"
            :hint="$stats['overdue_verifications'] ? __(':count waiting > 24 h', ['count' => $stats['overdue_verifications']]) : null" :href="route('admin.payments.index')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-stat-card :label="__('Net revenue this month')" :value="money($stats['revenue_month'])" :hint="$change($stats['revenue_change']).' '.__('vs same period last month')" />
        <x-stat-card :label="__('Occupancy this month')" :value="$stats['occupancy_month'].'%'" :hint="$change($stats['occupancy_change'], ' '.__('pts')).' '.__('vs same period last month')" />
    </div>

    <div class="card card-body">
        <h2 class="mb-3 font-semibold">{{ __('Net revenue, last 6 months (per branch)') }}</h2>
        <x-admin.chart :config="$revenueChart" :label="__('Net revenue, last 6 months (per branch)')" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body lg:col-span-2">
            <h2 class="mb-3 font-semibold">{{ __('Daily occupancy, last 30 days (%)') }}</h2>
            <x-admin.chart :config="$occChart" :label="__('Daily occupancy, last 30 days (%)')" />
        </div>
        <div class="card card-body">
            <h2 class="mb-3 font-semibold">{{ __('Best-selling room types this month') }}</h2>
            @php $max = max(1, $stats['top_room_types']->max('nights')); @endphp
            <ul class="space-y-3 text-sm">
                @forelse ($stats['top_room_types'] as $row)
                    <li>
                        <div class="flex justify-between"><span>{{ $row->room_type }} <span class="text-xs text-slate-400">{{ Str::after($row->branch, 'Nusa Stay ') }}</span></span><strong>{{ $row->nights }}</strong></div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-brand-600" style="width: {{ $row->nights / $max * 100 }}%"></div></div>
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No data yet.') }}</li>
                @endforelse
            </ul>
            <p class="mt-3 text-xs text-slate-400">{{ __('in room-nights sold') }}</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <h2 class="px-5 pt-5 font-semibold">{{ __('Today\'s check-ins') }}</h2>
        <div class="overflow-x-auto">
            <table class="table mt-3">
                <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Guest') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Room') }}</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stats['check_ins_today'] as $r)
                        <tr>
                            <td><a href="{{ route('admin.reservations.show', $r) }}" class="link font-mono">{{ $r->code }}</a></td>
                            <td>{{ $r->guest_name }}</td><td>{{ $r->branch->name }}</td><td>{{ $r->rooms->pluck('room.number')->join(', ') }}</td>
                            <td class="text-right"><form method="POST" action="{{ route('admin.reservations.check-in', $r) }}" onsubmit="return confirm('{{ __('Check in this guest?') }}')">@csrf<button class="btn-primary btn-sm">{{ __('Check-in') }}</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-500">{{ __('No check-ins scheduled for today.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
