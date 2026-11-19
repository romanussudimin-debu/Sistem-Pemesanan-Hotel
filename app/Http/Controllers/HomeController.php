<?php

// app/Http/Controllers/HomeController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** P-01 Beranda: form pencarian + kartu cabang dengan tarif termurah. */
    public function __invoke(): View
    {
        $branches = Branch::active()
            ->withMin(['roomTypes as min_price' => fn ($q) => $q->active()], 'base_price')
            ->orderBy('name')
            ->get();

        return view('home', [
            'branches' => $branches,
            'defaults' => [
                'check_in' => today()->addDay()->toDateString(),
                'check_out' => today()->addDays(2)->toDateString(),
                'guests' => 2,
                'rooms' => 1,
            ],
        ]);
    }
}
