<?php

// app/Support/helpers.php
// Helper format lokal (Tahap 4, 4.3.3). Dimuat otomatis lewat composer.json "autoload.files".

use Carbon\CarbonInterface;

if (! function_exists('money')) {
    /** ID: Rp1.200.000 · EN: IDR 1,200,000 */
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return app()->getLocale() === 'en'
            ? 'IDR '.number_format($amount, 0, '.', ',')
            : 'Rp'.number_format($amount, 0, ',', '.');
    }
}

if (! function_exists('num')) {
    /** Angka dengan pemisah ribuan sesuai bahasa. ID: 1.770 · EN: 1,770 */
    function num(float|int|string|null $value, int $decimals = 0): string
    {
        return app()->getLocale() === 'en'
            ? number_format((float) $value, $decimals, '.', ',')
            : number_format((float) $value, $decimals, ',', '.');
    }
}

if (! function_exists('date_local')) {
    /** ID: 19 Nov 2026 · EN: Nov 19, 2026 */
    function date_local(?CarbonInterface $date, bool $withDay = false): string
    {
        if (! $date) {
            return '–';
        }

        $format = app()->getLocale() === 'en' ? ($withDay ? 'D, M j, Y' : 'M j, Y') : ($withDay ? 'D, j M Y' : 'j M Y');

        return $date->locale(app()->getLocale())->translatedFormat($format);
    }
}

if (! function_exists('datetime_local')) {
    function datetime_local(?CarbonInterface $date): string
    {
        return $date ? date_local($date).', '.$date->format('H.i').' WITA' : '–';
    }
}
