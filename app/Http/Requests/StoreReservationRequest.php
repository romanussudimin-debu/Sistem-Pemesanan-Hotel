<?php

// app/Http/Requests/StoreReservationRequest.php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStayDates;
use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * UC-07 langkah 4: validasi checkout (BR-01 s.d. BR-04).
 * `guests` adalah array jumlah tamu per kamar, mis. [2, 1] = 2 kamar.
 */
class StoreReservationRequest extends FormRequest
{
    use ValidatesStayDates;

    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() ?? false;
    }

    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'integer', Rule::exists('room_types', 'id')->where('is_active', true)->whereNull('deleted_at')],
            ...$this->stayDateRules(),
            'guests' => ['required', 'array', 'min:1', 'max:'.config('hotel.booking.max_rooms')],
            'guests.*' => ['required', 'integer', 'min:1'],
            'guest_name' => ['required', 'string', 'max:100'],
            'guest_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{8,20}$/'],
            'special_request' => ['nullable', 'string', 'max:500'],
            'agree' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $v) => $this->validateStayLength($v),
            function (Validator $v) {
                // BR-04: tamu per kamar tidak boleh melebihi kapasitas tipe kamar
                $type = RoomType::find($this->input('room_type_id'));
                if ($type && collect($this->input('guests', []))->contains(fn ($g) => (int) $g > $type->capacity)) {
                    $v->errors()->add('guests', __('validation.custom.guests.capacity', ['max' => $type->capacity]));
                }
            },
        ];
    }

    /** Bentuk data yang diterima ReservationService::create(). */
    public function toServiceData(): array
    {
        return [
            'check_in' => $this->validated('check_in'),
            'check_out' => $this->validated('check_out'),
            'guest_name' => $this->validated('guest_name'),
            'guest_phone' => $this->validated('guest_phone'),
            'special_request' => $this->validated('special_request'),
            'items' => [[
                'room_type_id' => (int) $this->validated('room_type_id'),
                'guests' => array_map('intval', $this->validated('guests')),
            ]],
        ];
    }
}
