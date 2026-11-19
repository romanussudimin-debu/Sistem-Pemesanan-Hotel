<?php

// app/Http/Controllers/ReservationController.php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus as S;
use App\Http\Requests\CancelReservationRequest;
use App\Models\Reservation;
use App\Services\CancellationPolicy;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /** Tab filter riwayat (W-06). */
    private const TABS = [
        'active' => [S::PendingPayment, S::AwaitingVerification, S::Paid, S::CheckedIn],
        'pending' => [S::PendingPayment],
        'completed' => [S::Completed],
        'cancelled' => [S::Cancelled, S::Expired],
    ];

    /** P-10 Riwayat pesanan (UC-12, FR-19). Hanya reservasi milik pengguna (NFR-03). */
    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'all';

        $reservations = $request->user()->reservations()
            ->with(['branch', 'rooms.roomType'])
            ->when($tab !== 'all', fn ($q) => $q->status(...self::TABS[$tab]))
            ->when($request->query('q'), fn ($q, $code) => $q->where('code', 'like', '%'.trim($code).'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('reservations.index', compact('reservations', 'tab'));
    }

    /** P-11 Detail reservasi + estimasi refund untuk modal pembatalan. */
    public function show(Reservation $reservation, CancellationPolicy $policy): View
    {
        $this->authorize('view', $reservation);

        return view('reservations.show', [
            'reservation' => $reservation->load(['branch', 'rooms.roomType', 'rooms.room', 'payments', 'statusLogs']),
            'refund' => [
                'amount' => $policy->refundAmount($reservation),
                'percent' => $policy->refundPercentage($reservation),
                'days' => $policy->daysBeforeCheckIn($reservation),
            ],
        ]);
    }

    /** UC-13 */
    public function cancel(CancelReservationRequest $request, Reservation $reservation, ReservationService $service): RedirectResponse
    {
        $service->cancel($reservation, $request->user(), $request->validated('reason'));

        return redirect()->route('reservations.show', $reservation)
            ->with('success', __('messages.reservation_cancelled', ['code' => $reservation->code]));
    }

    /** P-12 Voucher siap cetak / simpan PDF dari browser (FR-18). */
    public function voucher(Reservation $reservation): View
    {
        $this->authorize('voucher', $reservation);

        return view('reservations.voucher', ['reservation' => $reservation->load(['branch', 'rooms.roomType', 'rooms.room', 'user'])]);
    }
}
