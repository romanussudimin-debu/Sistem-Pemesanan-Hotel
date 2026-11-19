<?php

// app/Services/ReportService.php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Laporan & dashboard (FR-30 s.d. FR-34). Definisi metrik mengikuti Tahap 3, 3.4:
 * - Okupansi = malam-kamar terjual ÷ malam-kamar tersedia; gabungan = Σ terjual ÷ Σ tersedia.
 * - Pendapatan bersih = Σ(total − refund) berdasarkan tanggal pelunasan (basis kas).
 */
class ReportService
{
    /** Ekspresi pengelompokan periode yang berjalan di MySQL/MariaDB maupun SQLite (dipakai di test). */
    private function periodExpr(string $column, string $groupBy): string
    {
        $mysql = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';

        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('{$mysql}', {$column})"
            : "DATE_FORMAT({$column}, '{$mysql}')";
    }

    /** Baris malam-kamar terjual (status Lunas/Check-in/Selesai) pada rentang tanggal. */
    private function soldNights(CarbonInterface $from, CarbonInterface $to, ?int $branchId): Builder
    {
        return DB::table('room_nights as rn')
            ->join('reservation_rooms as rr', 'rr.id', '=', 'rn.reservation_room_id')
            ->join('reservations as r', 'r.id', '=', 'rr.reservation_id')
            ->join('room_types as rt', 'rt.id', '=', 'rr.room_type_id')
            ->whereBetween('rn.stay_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('r.status', ReservationStatus::values(ReservationStatus::sold()))
            ->when($branchId, fn ($q) => $q->where('rt.branch_id', $branchId));
    }

    /** Jumlah kamar yang dihitung sebagai kapasitas per cabang (selain nonaktif). */
    private function roomCounts(): Collection
    {
        return DB::table('rooms')
            ->join('room_types as rt', 'rt.id', '=', 'rooms.room_type_id')
            ->whereNull('rooms.deleted_at')
            ->where('rooms.status', '<>', RoomStatus::Inactive->value)
            ->groupBy('rt.branch_id')
            ->pluck(DB::raw('COUNT(*)'), 'rt.branch_id')
            ->map(fn ($v) => (int) $v);
    }

    /**
     * FR-31: okupansi per cabang + baris gabungan.
     *
     * @return array{rows: Collection, total: array}
     */
    public function occupancy(CarbonInterface $from, CarbonInterface $to, ?int $branchId = null): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $rooms = $this->roomCounts();
        $sold = $this->soldNights($from, $to, $branchId)->groupBy('rt.branch_id')->pluck(DB::raw('COUNT(*)'), 'rt.branch_id');

        $rows = Branch::orderBy('name')->when($branchId, fn ($q) => $q->whereKey($branchId))->get()
            ->map(function (Branch $b) use ($rooms, $sold, $days) {
                $available = ($rooms[$b->id] ?? 0) * $days;
                $soldNights = (int) ($sold[$b->id] ?? 0);

                return [
                    'branch' => $b->name,
                    'branch_id' => $b->id,
                    'rooms' => $rooms[$b->id] ?? 0,
                    'available' => $available,
                    'sold' => $soldNights,
                    'rate' => $available ? round($soldNights / $available * 100, 1) : 0,
                ];
            });

        $available = $rows->sum('available');
        $soldTotal = $rows->sum('sold');

        return ['rows' => $rows, 'total' => [
            'rooms' => $rows->sum('rooms'),
            'available' => $available,
            'sold' => $soldTotal,
            'rate' => $available ? round($soldTotal / $available * 100, 1) : 0, // bukan rata-rata persentase
        ]];
    }

    /** Seri okupansi harian per cabang untuk grafik garis. */
    public function occupancyDaily(CarbonInterface $from, CarbonInterface $to, ?int $branchId = null): array
    {
        $rooms = $this->roomCounts();
        $sold = $this->soldNights($from, $to, $branchId)
            ->select('rt.branch_id', 'rn.stay_date', DB::raw('COUNT(*) as c'))
            ->groupBy('rt.branch_id', 'rn.stay_date')
            ->get()
            ->groupBy('branch_id');

        $labels = collect(CarbonPeriod::create($from, $to))->map->toDateString();
        $series = Branch::orderBy('name')->when($branchId, fn ($q) => $q->whereKey($branchId))->get()
            ->map(function (Branch $b) use ($sold, $rooms, $labels) {
                $byDate = ($sold[$b->id] ?? collect())->mapWithKeys(fn ($r) => [substr((string) $r->stay_date, 0, 10) => (int) $r->c]);
                $cap = max(1, $rooms[$b->id] ?? 0);

                return ['label' => $b->name, 'data' => $labels->map(fn ($d) => round(($byDate[$d] ?? 0) / $cap * 100, 1))->all()];
            });

        return ['labels' => $labels->all(), 'series' => $series->all()];
    }

    /** FR-32: pendapatan kotor, refund, dan bersih per periode per cabang. */
    public function revenue(CarbonInterface $from, CarbonInterface $to, ?int $branchId = null, string $groupBy = 'month'): Collection
    {
        $period = $this->periodExpr('r.paid_at', $groupBy);

        return DB::table('reservations as r')
            ->join('branches as b', 'b.id', '=', 'r.branch_id')
            ->whereNotNull('r.paid_at') // termasuk yang dibatalkan setelah lunas; refund dikurangkan
            ->whereBetween('r.paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($branchId, fn ($q) => $q->where('r.branch_id', $branchId))
            ->selectRaw("{$period} as period, b.name as branch, SUM(r.total_amount) as gross, SUM(r.refund_amount) as refund, SUM(r.total_amount - r.refund_amount) as net, COUNT(*) as bookings")
            ->groupByRaw("{$period}, b.name")
            ->orderBy('period')->orderBy('branch')
            ->get()
            ->map(fn ($r) => tap($r, function ($r) {
                $r->gross = (float) $r->gross;
                $r->refund = (float) $r->refund;
                $r->net = (float) $r->net;
            }));
    }

    /** FR-33: jumlah reservasi per periode dan status (berdasarkan tanggal dibuat). */
    public function reservationCounts(CarbonInterface $from, CarbonInterface $to, ?int $branchId = null, string $groupBy = 'month'): Collection
    {
        $period = $this->periodExpr('created_at', $groupBy);

        return DB::table('reservations')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw("{$period} as period, status, COUNT(*) as total")
            ->groupByRaw("{$period}, status")
            ->orderBy('period')
            ->get();
    }

    /** FR-34: tipe kamar terlaris berdasarkan malam-kamar terjual. */
    public function topRoomTypes(CarbonInterface $from, CarbonInterface $to, ?int $branchId = null, int $limit = 5): Collection
    {
        return $this->soldNights($from, $to, $branchId)
            ->join('branches as b', 'b.id', '=', 'rt.branch_id')
            ->selectRaw('b.name as branch, rt.name as room_type, COUNT(*) as nights')
            ->groupBy('b.name', 'rt.id', 'rt.name')
            ->orderByDesc('nights')
            ->limit($limit)
            ->get();
    }

    /** FR-30: ringkasan dashboard admin. */
    public function dashboard(?int $branchId = null): array
    {
        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $lastMonthStart = $monthStart->subMonth();
        $lastMonthSameDay = $lastMonthStart->addDays(min($today->day, $lastMonthStart->daysInMonth) - 1);

        $scoped = fn () => Reservation::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $net = fn ($from, $to) => (float) $scoped()->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])->sum(DB::raw('total_amount - refund_amount'));

        $revenueNow = $net($monthStart, $today);
        $revenuePrev = $net($lastMonthStart, $lastMonthSameDay);
        $occNow = $this->occupancy($monthStart, $today, $branchId)['total']['rate'];
        $occPrev = $this->occupancy($lastMonthStart, $lastMonthSameDay, $branchId)['total']['rate'];

        $sixMonthsAgo = $monthStart->subMonths(5);

        return [
            'today_bookings' => $scoped()->whereDate('created_at', $today)->count(),
            'today_check_ins' => $scoped()->whereDate('check_in', $today)->whereIn('status', ['paid', 'checked_in'])->count(),
            'today_check_outs' => $scoped()->whereDate('check_out', $today)->whereIn('status', ['checked_in', 'completed'])->count(),
            'pending_verifications' => Payment::where('status', PaymentStatus::Submitted)
                ->when($branchId, fn ($q) => $q->whereHas('reservation', fn ($r) => $r->where('branch_id', $branchId)))->count(),
            'overdue_verifications' => Payment::where('status', PaymentStatus::Submitted)->where('created_at', '<', now()->subDay())->count(),
            'revenue_month' => $revenueNow,
            'revenue_change' => $revenuePrev > 0 ? round(($revenueNow - $revenuePrev) / $revenuePrev * 100, 1) : null,
            'occupancy_month' => $occNow,
            'occupancy_change' => round($occNow - $occPrev, 1), // dalam poin persentase
            'revenue_by_branch' => $this->revenue($sixMonthsAgo, $today, $branchId),
            'occupancy_daily' => $this->occupancyDaily($today->subDays(29), $today, $branchId),
            'top_room_types' => $this->topRoomTypes($monthStart, $today->endOfMonth(), $branchId),
            'check_ins_today' => $scoped()->with(['user', 'branch', 'rooms.room'])->whereDate('check_in', $today)
                ->where('status', ReservationStatus::Paid)->orderBy('guest_name')->get(),
        ];
    }
}
