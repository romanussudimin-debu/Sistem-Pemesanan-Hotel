<?php

// app/Http/Controllers/Admin/PaymentVerificationController.php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Models\Payment;
use App\Services\PaymentVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentVerificationController extends Controller
{
    /** A-04 Antrean verifikasi (default: menunggu verifikasi, terlama di atas). */
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(PaymentStatus::class)]])['status'] ?? PaymentStatus::Submitted->value;

        $payments = Payment::with(['reservation.branch', 'reservation.user'])
            ->where('method', 'bank_transfer')
            ->where('status', $status)
            ->orderBy('created_at', $status === PaymentStatus::Submitted->value ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', compact('payments', 'status'));
    }

    /** W-10 Detail verifikasi. */
    public function show(Payment $payment): View
    {
        return view('admin.payments.show', [
            'payment' => $payment->load(['reservation.branch', 'reservation.user', 'verifier']),
            'attempt' => $payment->reservation->payments()->where('method', 'bank_transfer')->where('id', '<=', $payment->id)->count(),
        ]);
    }

    /** NFR-04: bukti transfer disajikan dari storage privat lewat rute admin, tidak pernah lewat URL publik. */
    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    public function approve(Request $request, Payment $payment, PaymentVerificationService $service): RedirectResponse
    {
        $reservation = $service->approve($payment, $request->user());

        return redirect()->route('admin.payments.index')->with('success', __('messages.payment_approved', ['code' => $reservation->code]));
    }

    public function reject(RejectPaymentRequest $request, Payment $payment, PaymentVerificationService $service): RedirectResponse
    {
        $service->reject($payment, $request->user(), $request->validated('reason'));

        return redirect()->route('admin.payments.index')->with('success', __('messages.payment_rejected'));
    }
}
