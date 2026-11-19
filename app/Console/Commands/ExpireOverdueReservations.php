<?php

// app/Console/Commands/ExpireOverdueReservations.php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reservations:expire')]
#[Description('Mengubah reservasi yang melewati batas bayar menjadi Kedaluwarsa dan melepas kamarnya (FR-16)')]
class ExpireOverdueReservations extends Command
{
    public function handle(ReservationService $service): int
    {
        $count = $service->expireOverdue();
        $this->info("{$count} reservasi kedaluwarsa.");

        return self::SUCCESS;
    }
}
