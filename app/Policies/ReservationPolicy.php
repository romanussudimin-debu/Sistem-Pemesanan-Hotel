<?php

// app/Policies/ReservationPolicy.php

namespace App\Policies;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;

/**
 * NFR-03: pelanggan hanya dapat mengakses reservasi miliknya sendiri; admin dapat mengakses semua.
 */
class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin() || $reservation->user_id === $user->id;
    }

    public function pay(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return ($user->isAdmin() || $reservation->user_id === $user->id) && $reservation->isCancellable(); // BR-15
    }

    /** FR-18: voucher hanya untuk reservasi lunas/check-in/selesai. */
    public function voucher(User $user, Reservation $reservation): bool
    {
        return $this->view($user, $reservation)
            && in_array($reservation->status, ReservationStatus::sold(), true);
    }
}
