<?php

// app/Exceptions/InvalidStatusTransitionException.php

namespace App\Exceptions;

use App\Enums\ReservationStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(public readonly ?ReservationStatus $from, public readonly ReservationStatus $to)
    {
        parent::__construct(sprintf(
            'Transisi status reservasi tidak diizinkan: %s -> %s',
            $from?->value ?? 'null',
            $to->value
        ));
    }
}
