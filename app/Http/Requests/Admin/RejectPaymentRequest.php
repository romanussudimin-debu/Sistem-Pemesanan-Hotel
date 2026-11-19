<?php

// app/Http/Requests/Admin/RejectPaymentRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:255']]; // UC-15 A1
    }
}
