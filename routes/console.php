<?php

// routes/console.php

use Illuminate\Support\Facades\Schedule;

// FR-16 / NFR-12: jalankan `php artisan schedule:work` (lokal) atau cron `* * * * * php artisan schedule:run` (server)
Schedule::command('reservations:expire')->everyFiveMinutes()->withoutOverlapping();
