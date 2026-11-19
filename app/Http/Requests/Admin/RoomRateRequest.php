<?php

// app/Http/Requests/Admin/RoomRateRequest.php

namespace App\Http\Requests\Admin;

use App\Models\RoomRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RoomRateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'exists:room_types,id'],
            'label' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            // FR-26: periode tarif per tipe kamar tidak boleh tumpang tindih
            $clash = RoomRate::overlapping(
                (int) $this->input('room_type_id'),
                $this->input('start_date'),
                $this->input('end_date'),
                $this->route('room_rate')?->id,
            )->first();

            if ($clash) {
                $v->errors()->add('start_date', __('messages.rate_overlap', ['label' => $clash->label]));
            }
        }];
    }
}
