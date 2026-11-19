<?php

// app/Http/Requests/UploadPaymentProofRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-11 / NFR-04: validasi bukti transfer (tipe & ukuran file) dan kepemilikan reservasi.
 */
class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pay', $this->route('reservation'));
    }

    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:'.implode(',', config('hotel.payment.proof_mimes')), 'max:'.config('hotel.payment.proof_max_kb')],
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_bank' => ['required', 'string', 'max:50'],
            'transfer_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}
