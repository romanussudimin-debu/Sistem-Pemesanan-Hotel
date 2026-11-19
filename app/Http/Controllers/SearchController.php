<?php

// app/Http/Controllers/SearchController.php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRoomsRequest;
use App\Models\Branch;
use App\Services\AvailabilityService;
use Illuminate\View\View;

class SearchController extends Controller
{
    /** P-02 Hasil pencarian (UC-05, FR-06). */
    public function __invoke(SearchRoomsRequest $request, AvailabilityService $availability): View
    {
        $branch = Branch::where('slug', $request->validated('branch'))->firstOrFail();
        [$checkIn, $checkOut] = [$request->checkIn(), $request->checkOut()];
        $guests = (int) $request->validated('guests');
        $rooms = (int) $request->validated('rooms');

        $results = $availability->searchByBranch($branch, $checkIn, $checkOut, $guests, $rooms);

        // UC-05 A3: pengurutan ulang tanpa menghitung ulang ketersediaan
        $results = match ($request->validated('sort')) {
            'price_desc' => $results->sortBy([['fits', 'desc'], ['base_price', 'desc']])->values(),
            'capacity' => $results->sortBy([['fits', 'desc'], ['capacity', 'desc']])->values(),
            default => $results,
        };

        $alternatives = $results->where('fits', true)->isEmpty()
            ? $availability->alternativeBranches($branch, $checkIn, $checkOut, $guests, $rooms) // UC-05 A2
            : collect();

        return view('search.index', [
            'branch' => $branch,
            'branches' => Branch::active()->orderBy('name')->get(),
            'results' => $results,
            'alternatives' => $alternatives,
            'criteria' => $request->validated() + ['nights' => $checkIn->diffInDays($checkOut)],
        ]);
    }
}
