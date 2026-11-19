<?php

// app/Http/Requests/CancelReservationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('reservation')); // BR-15 + kepemilikan
    }

    public function rules(): array
    {
        // UC-13 A3: alasan wajib bila yang membatalkan adalah admin
        return [
            'reason' => [$this->user()->isAdmin() ? 'required' : 'nullable', 'string', 'max:255'],
        ];
    }
}
