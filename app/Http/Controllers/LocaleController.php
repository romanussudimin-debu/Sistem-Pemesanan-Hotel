<?php

// app/Http/Controllers/LocaleController.php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /** FR-05: GET /locale/{id|en} → simpan di sesi & profil, kembali ke halaman yang sama. */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('hotel.locales'), true), 404);

        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back();
    }
}
