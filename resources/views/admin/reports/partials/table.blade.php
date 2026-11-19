{{-- resources/views/admin/reports/partials/table.blade.php — tabel laporan per tab (dipakai halaman & versi cetak) --}}
<div class="overflow-x-auto">
@switch($tab)
    @case('revenue')
        <table class="table">
            <thead><tr><th>{{ __('Period') }}</th><th>{{ __('Branch') }}</th><th class="text-right">{{ __('Bookings') }}</th><th class="text-right">{{ __('Gross') }}</th><th class="text-right">{{ __('Refund') }}</th><th class="text-right">{{ __('Net') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($revenue as $r)<tr><td>{{ $r->period }}</td><td>{{ $r->branch }}</td><td class="text-right">{{ $r->bookings }}</td><td class="text-right">{{ money($r->gross) }}</td><td class="text-right">{{ money($r->refund) }}</td><td class="text-right font-medium">{{ money($r->net) }}</td></tr>
                @empty<tr><td colspan="6" class="text-center text-slate-500">{{ __('No data yet.') }}</td></tr>@endforelse
            </tbody>
            <tfoot><tr class="font-bold"><td colspan="2">{{ __('CONSOLIDATED') }}</td><td class="px-4 py-3 text-right">{{ $revenue->sum('bookings') }}</td><td class="px-4 py-3 text-right">{{ money($revenue->sum('gross')) }}</td><td class="px-4 py-3 text-right">{{ money($revenue->sum('refund')) }}</td><td class="px-4 py-3 text-right">{{ money($revenue->sum('net')) }}</td></tr></tfoot>
        </table>
        @break
    @case('reservations')
        <table class="table">
            <thead><tr><th>{{ __('Period') }}</th>@foreach (App\Enums\ReservationStatus::cases() as $s)<th class="text-right">{{ $s->label() }}</th>@endforeach<th class="text-right">{{ __('Total') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reservations->groupBy('period') as $period => $rows)
                    <tr><td>{{ $period }}</td>@foreach (App\Enums\ReservationStatus::cases() as $s)<td class="text-right">{{ $rows->firstWhere('status', $s->value)?->total ?? 0 }}</td>@endforeach<td class="text-right font-medium">{{ $rows->sum('total') }}</td></tr>
                @empty<tr><td colspan="9" class="text-center text-slate-500">{{ __('No data yet.') }}</td></tr>@endforelse
            </tbody>
        </table>
        @break
    @case('top')
        <table class="table">
            <thead><tr><th>#</th><th>{{ __('Branch') }}</th><th>{{ __('Room type') }}</th><th class="text-right">{{ __('Room-nights sold') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($top as $i => $r)<tr><td>{{ $i + 1 }}</td><td>{{ $r->branch }}</td><td>{{ $r->room_type }}</td><td class="text-right font-medium">{{ $r->nights }}</td></tr>
                @empty<tr><td colspan="4" class="text-center text-slate-500">{{ __('No data yet.') }}</td></tr>@endforelse
            </tbody>
        </table>
        @break
    @default
        <table class="table">
            <thead><tr><th>{{ __('Branch') }}</th><th class="text-right">{{ __('Rooms') }}</th><th class="text-right">{{ __('Room-nights available') }}</th><th class="text-right">{{ __('Room-nights sold') }}</th><th class="text-right">{{ __('Occupancy') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($occupancy['rows'] as $r)<tr><td>{{ $r['branch'] }}</td><td class="text-right">{{ $r['rooms'] }}</td><td class="text-right">{{ num($r['available']) }}</td><td class="text-right">{{ num($r['sold']) }}</td><td class="text-right">{{ $r['rate'] }}%</td></tr>@endforeach
            </tbody>
            <tfoot><tr class="font-bold"><td class="px-4 py-3">{{ __('CONSOLIDATED') }}</td><td class="px-4 py-3 text-right">{{ $occupancy['total']['rooms'] }}</td><td class="px-4 py-3 text-right">{{ num($occupancy['total']['available']) }}</td><td class="px-4 py-3 text-right">{{ num($occupancy['total']['sold']) }}</td><td class="px-4 py-3 text-right">{{ $occupancy['total']['rate'] }}%</td></tr></tfoot>
        </table>
@endswitch
</div>
<p class="mt-2 text-xs text-slate-500">
    @if ($tab === 'revenue'){{ __('Net revenue = total − refund, recognised on the payment date (cash basis).') }}
    @elseif ($tab === 'occupancy'){{ __('Consolidated occupancy = Σ sold ÷ Σ available (not an average of percentages). Inactive rooms are excluded from capacity.') }}
    @elseif ($tab === 'top'){{ __('Ranked by room-nights sold (paid, checked-in and completed bookings).') }}
    @else{{ __('Grouped by booking creation date.') }}@endif
</p>
