<?php

// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Di balik reverse proxy (GitHub Codespaces, load balancer) agar URL https & host publik dibuat dengan benar.
        // Hanya aktif bila TRUSTED_PROXIES diisi; pada server biasa tetap nonaktif.
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));

            // Cadangan bila proxy tidak mengirim X-Forwarded-Proto: ikuti skema APP_URL
            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
