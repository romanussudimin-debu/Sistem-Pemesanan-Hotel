<?php

// app/Http/Controllers/RoomTypeController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\CancellationPolicy;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomTypeController extends Controller
{
    /** P-03 Detail tipe kamar (UC-06, FR-07, FR-08). Rute memakai scoped binding cabang → tipe kamar. */
    public function show(Request $request, Branch $branch, RoomType $roomType, AvailabilityService $availability, PricingService $pricing): View
    {
        abort_unless($branch->is_active && $roomType->is_active, 404);

        // Tanggal opsional dari hasil pencarian; default besok–lusa. Tanggal tidak valid diabaikan.
        $checkIn = rescue(fn () => CarbonImmutable::parse($request->query('check_in')), null, false);
        $checkOut = rescue(fn () => CarbonImmutable::parse($request->query('check_out')), null, false);
        if (! $checkIn || ! $checkOut || $checkIn->lt(today()) || $checkOut->lte($checkIn)) {
            $checkIn = CarbonImmutable::today()->addDay();
            $checkOut = $checkIn->addDay();
        }
        $rooms = max(1, min((int) $request->query('rooms', 1), config('hotel.booking.max_rooms')));

        return view('rooms.show', [
            'branch' => $branch,
            'roomType' => $roomType->load('facilities', 'images', 'rates'),
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'rooms' => $rooms,
            'guests' => (int) $request->query('guests', min(2, $roomType->capacity)),
            'available' => $availability->availableCount($roomType, $checkIn, $checkOut),
            'quote' => $pricing->quote($roomType, $checkIn, $checkOut, $rooms),
            'freeCancellationUntil' => CancellationPolicy::freeCancellationUntil($checkIn),
        ]);
    }
}
