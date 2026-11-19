<?php

// app/Http/Requests/Admin/FacilityRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FacilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_id' => ['required', 'string', 'max:50'],
            'name_en' => ['required', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50', 'alpha_dash'],
        ];
    }
}
