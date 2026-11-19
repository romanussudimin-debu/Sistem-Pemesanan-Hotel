<?php

// app/Http/Requests/Admin/ReportFilterRequest.php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'group_by' => ['nullable', Rule::in(['day', 'month'])],
            'tab' => ['nullable', Rule::in(['occupancy', 'revenue', 'reservations', 'top'])],
        ];
    }

    /** Default: bulan berjalan. */
    public function from(): CarbonImmutable
    {
        return $this->validated('from') ? CarbonImmutable::parse($this->validated('from')) : CarbonImmutable::today()->startOfMonth();
    }

    public function to(): CarbonImmutable
    {
        return $this->validated('to') ? CarbonImmutable::parse($this->validated('to')) : CarbonImmutable::today()->endOfMonth()->startOfDay();
    }

    public function branchId(): ?int
    {
        return $this->validated('branch_id') ? (int) $this->validated('branch_id') : null;
    }

    public function groupBy(): string
    {
        return $this->validated('group_by') ?? 'day';
    }

    public function tab(): string
    {
        return $this->validated('tab') ?? 'occupancy';
    }
}
