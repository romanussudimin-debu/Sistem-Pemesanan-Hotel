<?php

// app/Exceptions/RoomNotAvailableException.php

namespace App\Exceptions;

/**
 * Dilempar bila kamar tidak lagi cukup saat reservasi disimpan (UC-07 A3),
 * baik dari cek ulang di dalam transaksi (lapis 2) maupun indeks unik room_nights (lapis 3).
 */
class RoomNotAvailableException extends BusinessRuleException
{
    public function __construct(public readonly ?string $roomTypeName = null)
    {
        parent::__construct('messages.room_just_sold_out', ['room' => $roomTypeName ?? '']);
    }
}
