<?php

// app/Http/Controllers/PaymentCallbackController.php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook server-ke-server dari gateway (UC-10 langkah 6). Tanpa sesi/CSRF; keasliannya dijamin signature HMAC.
 * Contoh: POST /payments/mockpay/callback {gateway_ref, amount, result, signature}
 */
class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse
    {
        try {
            $payment = $payments->handleCallback($request->only(['gateway_ref', 'amount', 'result', 'signature']));
        } catch (BusinessRuleException) {
            return response()->json(['ok' => false, 'message' => 'invalid callback'], 400); // UC-09 E1
        }

        return response()->json(['ok' => true, 'status' => $payment->status->value]);
    }
}
