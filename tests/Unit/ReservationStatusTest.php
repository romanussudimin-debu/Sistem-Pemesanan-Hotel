<?php

// tests/Unit/ReservationStatusTest.php

namespace Tests\Unit;

use App\Enums\ReservationStatus as S;
use PHPUnit\Framework\TestCase;

class ReservationStatusTest extends TestCase
{
    public function test_alur_normal_diizinkan(): void
    {
        $this->assertTrue(S::PendingPayment->canTransitionTo(S::AwaitingVerification));
        $this->assertTrue(S::AwaitingVerification->canTransitionTo(S::Paid));
        $this->assertTrue(S::Paid->canTransitionTo(S::CheckedIn));
        $this->assertTrue(S::CheckedIn->canTransitionTo(S::Completed));
    }

    public function test_transisi_terlarang_ditolak(): void
    {
        $this->assertFalse(S::Completed->canTransitionTo(S::Cancelled));   // status akhir
        $this->assertFalse(S::CheckedIn->canTransitionTo(S::Cancelled));   // BR-15
        $this->assertFalse(S::AwaitingVerification->canTransitionTo(S::Expired)); // BR-10
        $this->assertFalse(S::Cancelled->canTransitionTo(S::Paid));
    }

    public function test_status_yang_boleh_dibatalkan_sesuai_br15(): void
    {
        $cancellable = array_filter(S::cases(), fn (S $s) => $s->isCancellable());

        $this->assertEqualsCanonicalizing(
            [S::PendingPayment, S::AwaitingVerification, S::Paid],
            array_values($cancellable)
        );
    }

    public function test_status_dibatalkan_dan_kedaluwarsa_tidak_menahan_inventaris(): void
    {
        $this->assertNotContains(S::Cancelled, S::holdingInventory());
        $this->assertNotContains(S::Expired, S::holdingInventory());
    }
}
