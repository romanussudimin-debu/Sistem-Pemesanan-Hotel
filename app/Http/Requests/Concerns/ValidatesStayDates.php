<?php

// app/Http/Requests/Concerns/ValidatesStayDates.php

namespace App\Http\Requests\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * Aturan tanggal menginap yang dipakai bersama oleh pencarian dan checkout (BR-01, BR-02).
 */
trait ValidatesStayDates
{
    protected function stayDateRules(): array
    {
        $maxDate = today()->addDays(config('hotel.booking.max_days_ahead'))->toDateString();

        return [
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', "before_or_equal:{$maxDate}"],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
        ];
    }

    protected function validateStayLength(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['check_in', 'check_out'])) {
            return;
        }

        $nights = CarbonImmutable::parse($this->input('check_in'))->diffInDays(CarbonImmutable::parse($this->input('check_out')));
        $max = config('hotel.booking.max_nights');

        if ($nights > $max) {
            $validator->errors()->add('check_out', __('validation.custom.check_out.max_nights', ['max' => $max]));
        }
    }

    public function checkIn(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('check_in'));
    }

    public function checkOut(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('check_out'));
    }
}
