<?php

// app/Http/Middleware/SetLocale.php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-05: bahasa antarmuka diambil dari sesi → profil pengguna → default aplikasi.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('hotel.locales');
        $locale = $request->session()->get('locale') ?? $request->user()?->locale ?? config('app.locale');

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
            Carbon::setLocale($locale);          // nama bulan/hari pada translatedFormat()
            CarbonImmutable::setLocale($locale);
        }

        return $next($request);
    }
}
