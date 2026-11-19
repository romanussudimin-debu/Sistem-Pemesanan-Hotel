<?php

// app/Http/Controllers/CheckoutController.php

namespace App\Http\Controllers;

use App\Exceptions\RoomNotAvailableException;
use App\Http\Requests\CheckoutPreviewRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /** P-06 Checkout / data tamu (UC-07 langkah 2). */
    public function create(CheckoutPreviewRequest $request, AvailabilityService $availability, PricingService $pricing): View|RedirectResponse
    {
        $type = RoomType::with('branch', 'rates', 'images')->findOrFail($request->validated('room_type'));
        [$checkIn, $checkOut] = [$request->checkIn(), $request->checkOut()];
        $rooms = (int) $request->validated('rooms');

        $available = $availability->availableCount($type, $checkIn, $checkOut);
        if ($available < $rooms) {
            return $this->backToSearch($type, $request->validated(), new RoomNotAvailableException($type->name));
        }

        // Bagi tamu ke tiap kamar sebagai nilai awal form (BR-04)
        $guests = (int) $request->validated('guests');
        $perRoom = collect(range(1, $rooms))->map(fn ($i) => min($type->capacity, max(1, intdiv($guests + $rooms - $i, $rooms))))->all();

        return view('checkout.create', [
            'roomType' => $type,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'rooms' => $rooms,
            'guestsPerRoom' => $perRoom,
            'available' => $available,
            'quote' => $pricing->quote($type, $checkIn, $checkOut, $rooms),
            'user' => $request->user(),
        ]);
    }

    /** UC-07 langkah 4–7. */
    public function store(StoreReservationRequest $request, ReservationService $service): RedirectResponse
    {
        try {
            $reservation = $service->create($request->user(), $request->toServiceData());
        } catch (RoomNotAvailableException $e) {
            // UC-07 A3: kembali ke hasil pencarian dengan ketersediaan terbaru
            $type = RoomType::with('branch')->find($request->validated('room_type_id'));

            return $this->backToSearch($type, $request->validated() + ['rooms' => count($request->validated('guests'))], $e);
        }

        return redirect()->route('payments.show', $reservation)
            ->with('success', __('messages.reservation_created', ['code' => $reservation->code]));
    }

    private function backToSearch(RoomType $type, array $input, RoomNotAvailableException $e): RedirectResponse
    {
        $guests = $input['guests'] ?? 1;

        return redirect()->route('search', [
            'branch' => $type->branch->slug,
            'check_in' => $input['check_in'],
            'check_out' => $input['check_out'],
            'guests' => is_array($guests) ? array_sum($guests) : $guests,
            'rooms' => $input['rooms'] ?? 1,
        ])->with('error', $e->userMessage());
    }
}
