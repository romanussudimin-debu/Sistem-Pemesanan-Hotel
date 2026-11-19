<?php

// app/Http/Controllers/BranchController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\View\View;

class BranchController extends Controller
{
    /** P-04 Daftar cabang */
    public function index(): View
    {
        return view('branches.index', [
            'branches' => Branch::active()->withMin(['roomTypes as min_price' => fn ($q) => $q->active()], 'base_price')->orderBy('name')->get(),
        ]);
    }

    /** P-04 Detail cabang beserta tipe kamarnya */
    public function show(Branch $branch): View
    {
        abort_unless($branch->is_active, 404);

        return view('branches.show', [
            'branch' => $branch->load(['roomTypes' => fn ($q) => $q->active()->with('facilities', 'images')->orderBy('base_price')]),
        ]);
    }
}
