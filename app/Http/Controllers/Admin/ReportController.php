<?php

// app/Http/Controllers/Admin/ReportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** A-10 Laporan (FR-31 s.d. FR-35). */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportFilterRequest $request): View
    {
        return view('admin.reports.index', $this->data($request) + ['branches' => Branch::orderBy('name')->get()]);
    }

    /** Versi siap cetak / simpan PDF dari browser. */
    public function print(ReportFilterRequest $request): View
    {
        return view('admin.reports.print', $this->data($request));
    }

    /** FR-35: ekspor CSV tab aktif (dapat dibuka di Excel). */
    public function export(ReportFilterRequest $request): StreamedResponse
    {
        $data = $this->data($request);
        [$header, $rows] = match ($data['tab']) {
            'revenue' => [['period', 'branch', 'bookings', 'gross', 'refund', 'net'],
                $data['revenue']->map(fn ($r) => [$r->period, $r->branch, $r->bookings, $r->gross, $r->refund, $r->net])],
            'reservations' => [['period', 'status', 'total'],
                $data['reservations']->map(fn ($r) => [$r->period, $r->status, $r->total])],
            'top' => [['branch', 'room_type', 'room_nights_sold'],
                $data['top']->map(fn ($r) => [$r->branch, $r->room_type, $r->nights])],
            default => [['branch', 'rooms', 'room_nights_available', 'room_nights_sold', 'occupancy_pct'],
                $data['occupancy']['rows']->map(fn ($r) => [$r['branch'], $r['rooms'], $r['available'], $r['sold'], $r['rate']])
                    ->push(['TOTAL', $data['occupancy']['total']['rooms'], $data['occupancy']['total']['available'],
                        $data['occupancy']['total']['sold'], $data['occupancy']['total']['rate']])],
        };

        $filename = sprintf('laporan-%s-%s_%s.csv', $data['tab'], $data['from']->format('Ymd'), $data['to']->format('Ymd'));

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function data(ReportFilterRequest $request): array
    {
        [$from, $to, $branchId, $groupBy] = [$request->from(), $request->to(), $request->branchId(), $request->groupBy()];

        return [
            'from' => $from,
            'to' => $to,
            'branchId' => $branchId,
            'groupBy' => $groupBy,
            'tab' => $request->tab(),
            'occupancy' => $this->reports->occupancy($from, $to, $branchId),
            'occupancyDaily' => $this->reports->occupancyDaily($from, $to, $branchId),
            'revenue' => $this->reports->revenue($from, $to, $branchId, $groupBy),
            'reservations' => $this->reports->reservationCounts($from, $to, $branchId, $groupBy),
            'top' => $this->reports->topRoomTypes($from, $to, $branchId),
        ];
    }
}
