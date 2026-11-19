<?php

// app/Http/Controllers/Admin/ReservationController.php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelReservationRequest;
use App\Models\Branch;
use App\Models\Reservation;
use App\Services\CancellationPolicy;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /** A-02 Daftar reservasi dengan filter cabang, status, rentang check-in, dan pencarian (FR-28). */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'status' => ['nullable', Rule::enum(ReservationStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $reservations = Reservation::with(['branch', 'user', 'rooms.room'])
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('check_in', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('check_in', '<=', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('code', 'like', "%{$v}%")->orWhere('guest_name', 'like', "%{$v}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'branches' => Branch::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    /** A-03 Detail reservasi + riwayat status + percobaan pembayaran. */
    public function show(Reservation $reservation, CancellationPolicy $policy): View
    {
        return view('admin.reservations.show', [
            'reservation' => $reservation->load(['branch', 'user', 'rooms.roomType', 'rooms.room', 'payments.verifier', 'statusLogs.actor', 'canceller']),
            'refundEstimate' => $policy->refundAmount($reservation),
        ]);
    }

    public function checkIn(Request $request, Reservation $reservation, ReservationService $service): RedirectResponse
    {
        $service->checkIn($reservation, $request->user());

        return back()->with('success', __('messages.checked_in', ['code' => $reservation->code]));
    }

    public function checkOut(Request $request, Reservation $reservation, ReservationService $service): RedirectResponse
    {
        $service->checkOut($reservation, $request->user());

        return back()->with('success', __('messages.checked_out', ['code' => $reservation->code]));
    }

    /** UC-13 A3: pembatalan oleh admin (alasan wajib), termasuk kasus no-show. */
    public function cancel(CancelReservationRequest $request, Reservation $reservation, ReservationService $service): RedirectResponse
    {
        $service->cancel($reservation, $request->user(), $request->validated('reason'));

        return back()->with('success', __('messages.reservation_cancelled', ['code' => $reservation->code]));
    }
}
