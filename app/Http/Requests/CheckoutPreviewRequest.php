<?php

// app/Http/Requests/CheckoutPreviewRequest.php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStayDates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Parameter halaman checkout (GET) yang datang dari hasil pencarian / detail kamar. */
class CheckoutPreviewRequest extends FormRequest
{
    use ValidatesStayDates;

    public function rules(): array
    {
        return [
            'room_type' => ['required', 'integer', Rule::exists('room_types', 'id')->where('is_active', true)->whereNull('deleted_at')],
            ...$this->stayDateRules(),
            'rooms' => ['required', 'integer', 'min:1', 'max:'.config('hotel.booking.max_rooms')],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $v) => $this->validateStayLength($v)];
    }
}
