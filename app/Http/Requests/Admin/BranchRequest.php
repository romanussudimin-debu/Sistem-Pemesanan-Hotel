<?php

// app/Http/Requests/Admin/BranchRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('branch')?->id;

        return [
            'code' => ['required', 'string', 'size:3', 'alpha', Rule::unique('branches', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'description_id' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'bank_name' => ['required', 'string', 'max:50'],
            'bank_account_no' => ['required', 'string', 'max:30'],
            'bank_account_name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper((string) $this->input('code')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
