<?php

// app/Http/Controllers/MockPayController.php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * P-08 "MockPay": halaman tiruan payment gateway. BUKAN pembayaran nyata.
 * Tombol Berhasil/Gagal membuat payload bertanda tangan HMAC, persis seperti yang dikirim gateway sungguhan
 * ke webhook, lalu memprosesnya lewat PaymentService::handleCallback().
 */
class MockPayController extends Controller
{
    public function show(Request $request, string $ref): View|RedirectResponse
    {
        $payment = $this->findOwned($request, $ref);

        if ($payment->status !== PaymentStatus::Pending) {
            return redirect()->route('payments.status', $payment->reservation);
        }

        return view('mockpay.show', ['payment' => $payment->load('reservation.branch')]);
    }

    public function complete(Request $request, string $ref, PaymentService $payments): RedirectResponse
    {
        $payment = $this->findOwned($request, $ref);
        $result = $request->validate(['result' => ['required', 'in:success,failed']])['result'];
        $amount = number_format((float) $payment->amount, 2, '.', '');

        $payments->handleCallback([
            'gateway_ref' => $payment->gateway_ref,
            'amount' => $amount,
            'result' => $result,
            'signature' => PaymentService::signature($payment->gateway_ref, $amount, $result),
        ]);

        return redirect()->route('payments.status', $payment->reservation)->with(
            $result === 'success' ? 'success' : 'error',
            __($result === 'success' ? 'messages.payment_success' : 'messages.payment_failed'),
        );
    }

    private function findOwned(Request $request, string $ref): Payment
    {
        $payment = Payment::with('reservation')->where('gateway_ref', $ref)->firstOrFail();
        abort_unless($payment->reservation->user_id === $request->user()->id, 403);

        return $payment;
    }
}
