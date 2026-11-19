<?php

// app/Http/Requests/SearchRoomsRequest.php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStayDates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SearchRoomsRequest extends FormRequest
{
    use ValidatesStayDates;

    public function rules(): array
    {
        return [
            'branch' => ['required', Rule::exists('branches', 'slug')->where('is_active', true)->whereNull('deleted_at')],
            ...$this->stayDateRules(),
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'rooms' => ['required', 'integer', 'min:1', 'max:'.config('hotel.booking.max_rooms')], // BR-03
            'sort' => ['nullable', Rule::in(['price_asc', 'price_desc', 'capacity'])],
        ];
    }

    public function after(): array
    {
        return [fn (Validator $v) => $this->validateStayLength($v)];
    }
}
