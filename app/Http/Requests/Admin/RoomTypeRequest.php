<?php

// app/Http/Requests/Admin/RoomTypeRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoomTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('room_type')?->id;

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', Rule::unique('room_types')->where('branch_id', $this->input('branch_id'))->ignore($id)],
            'description_id' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'size_sqm' => ['nullable', 'integer', 'min:5', 'max:1000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'is_active' => ['boolean'],
            'facilities' => ['array'],
            'facilities.*' => ['exists:facilities,id'],
            'images' => ['array', 'max:8'],
            'images.*' => ['image', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
