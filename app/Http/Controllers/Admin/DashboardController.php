<?php

// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** A-01 Dashboard (FR-30). Filter cabang global: kosong = gabungan. */
    public function __invoke(Request $request, ReportService $reports): View
    {
        $branchId = $request->integer('branch_id') ?: null;

        return view('admin.dashboard', [
            'stats' => $reports->dashboard($branchId),
            'branches' => Branch::orderBy('name')->get(),
            'branchId' => $branchId,
        ]);
    }
}
