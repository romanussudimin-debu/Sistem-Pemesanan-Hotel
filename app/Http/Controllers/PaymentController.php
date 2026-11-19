<?php

// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Reservation;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /** P-07 Halaman pembayaran (UC-09 langkah 2). */
    public function show(Reservation $reservation): View|RedirectResponse
    {
        $this->authorize('pay', $reservation);

        if ($reservation->status !== ReservationStatus::PendingPayment) {
            return redirect()->route('payments.status', $reservation);
        }

        return view('payments.show', [
            'reservation' => $reservation->load('branch', 'rooms.roomType'),
            'lastRejected' => $reservation->payments()->where('status', 'rejected')->first(), // UC-09 A4
        ]);
    }

    /** UC-10 langkah 3–4: membuat percobaan bayar lalu menuju halaman MockPay. */
    public function gateway(Reservation $reservation, PaymentService $payments): RedirectResponse
    {
        $this->authorize('pay', $reservation);

        $payment = $payments->startGateway($reservation);

        return redirect()->route('mockpay.show', $payment->gateway_ref);
    }

    /** UC-11: unggah bukti transfer. */
    public function proof(UploadPaymentProofRequest $request, Reservation $reservation, PaymentService $payments): RedirectResponse
    {
        $payments->submitProof($reservation, $request->file('proof'), $request->validated(), $request->user());

        return redirect()->route('payments.status', $reservation)->with('success', __('messages.proof_uploaded'));
    }

    /** P-09 Status pembayaran (sukses / menunggu verifikasi / gagal / kedaluwarsa). */
    public function status(Reservation $reservation): View
    {
        $this->authorize('view', $reservation);

        return view('payments.status', ['reservation' => $reservation->load('branch', 'latestPayment')]);
    }
}
