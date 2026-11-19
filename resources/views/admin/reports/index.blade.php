{{-- resources/views/admin/reports/index.blade.php — A-10 Laporan (W-09, FR-31 s.d. FR-35) --}}
@php
    $palette = ['#0f766e', '#d97706', '#4f46e5'];
    $query = request()->except('tab');
    $chart = match ($tab) {
        'revenue' => (function () use ($revenue, $palette) {
            $periods = $revenue->pluck('period')->unique()->sort()->values();
            return ['type' => 'bar', 'data' => ['labels' => $periods->all(), 'datasets' => $revenue->groupBy('branch')->values()->map(fn ($rows, $i) => [
                'label' => $rows->first()->branch, 'backgroundColor' => $palette[$i % 3],
                'data' => $periods->map(fn ($p) => round(($rows->firstWhere('period', $p)?->net ?? 0) / 1e6, 2))->all()])->all()],
                'options' => ['maintainAspectRatio' => false, 'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'title' => ['display' => true, 'text' => __('Million Rp')]]]]];
        })(),
        'reservations' => (function () use ($reservations, $palette) {
            $periods = $reservations->pluck('period')->unique()->sort()->values();
            $colors = ['#d97706', '#0284c7', '#059669', '#4f46e5', '#64748b', '#dc2626', '#a1a1aa'];
            return ['type' => 'bar', 'data' => ['labels' => $periods->all(), 'datasets' => collect(App\Enums\ReservationStatus::cases())->values()->map(fn ($s, $i) => [
                'label' => $s->label(), 'backgroundColor' => $colors[$i],
                'data' => $periods->map(fn ($p) => (int) ($reservations->where('period', $p)->firstWhere('status', $s->value)?->total ?? 0))->all()])->all()],
                'options' => ['maintainAspectRatio' => false, 'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true]]]];
        })(),
        'top' => ['type' => 'bar', 'data' => ['labels' => $top->map(fn ($r) => $r->room_type.' ('.Str::after($r->branch, 'Nusa Stay ').')')->all(),
            'datasets' => [['label' => __('Room-nights sold'), 'data' => $top->pluck('nights')->all(), 'backgroundColor' => '#0f766e']]],
            'options' => ['indexAxis' => 'y', 'maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]]],
        default => ['type' => 'line', 'data' => ['labels' => collect($occupancyDaily['labels'])->map(fn ($d) => substr($d, 8, 2).'/'.substr($d, 5, 2))->all(), 'datasets' => collect($occupancyDaily['series'])->values()->map(fn ($s, $i) => $s + ['borderColor' => $palette[$i % 3], 'tension' => 0.3, 'pointRadius' => 0])->all()],
            'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['min' => 0, 'max' => 100]]]],
    };
@endphp
<x-admin-layout :title="__('Reports')">
    <form method="GET" class="card card-body grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div><label class="label" for="from">{{ __('From') }}</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
        <div><label class="label" for="to">{{ __('To') }}</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
        <div><label class="label" for="branch_id">{{ __('Branch') }}</label><select id="branch_id" name="branch_id" class="input"><option value="">{{ __('All branches') }}</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="label" for="group_by">{{ __('Group by') }}</label><select id="group_by" name="group_by" class="input"><option value="day">{{ __('Day') }}</option><option value="month" @selected($groupBy === 'month')>{{ __('Month') }}</option></select></div>
        <button class="btn-primary">{{ __('Apply') }}</button>
        <div class="flex gap-2 lg:justify-end">
            <a href="{{ route('admin.reports.export', request()->query() + ['tab' => $tab]) }}" class="btn-secondary btn-sm">CSV / Excel</a>
            <a href="{{ route('admin.reports.print', request()->query() + ['tab' => $tab]) }}" target="_blank" class="btn-secondary btn-sm">PDF</a>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card :label="__('Consolidated occupancy')" :value="$occupancy['total']['rate'].'%'" :hint="num($occupancy['total']['sold']).' / '.num($occupancy['total']['available']).' '.__('room-nights')" />
        <x-stat-card :label="__('Net revenue')" :value="money($revenue->sum('net'))" :hint="__('Refunds').': '.money($revenue->sum('refund'))" />
        <x-stat-card :label="__('Bookings created')" :value="num($reservations->sum('total'))" />
    </div>

    <nav class="flex flex-wrap gap-2 border-b border-slate-200 text-sm">
        @foreach (['occupancy' => __('Occupancy'), 'revenue' => __('Revenue'), 'reservations' => __('Bookings'), 'top' => __('Best-sellers')] as $key => $label)
            <a href="{{ route('admin.reports.index', $query + ['tab' => $key]) }}" class="-mb-px border-b-2 px-4 py-2 font-medium {{ $tab === $key ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="card card-body"><x-admin.chart :config="$chart" :label="__('Report chart')" height="h-72" /></div>
    <div class="card card-body">@include('admin.reports.partials.table')</div>
</x-admin-layout>
