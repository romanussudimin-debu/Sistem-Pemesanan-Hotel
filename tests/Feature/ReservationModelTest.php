<?php

// tests/Feature/ReservationModelTest.php

namespace Tests\Feature;

use App\Enums\ReservationStatus as S;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\BranchSeeder;
use Database\Seeders\FacilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_batas_bayar_24_jam_bila_check_in_masih_jauh(): void
    {
        $created = CarbonImmutable::parse('2026-11-19 10:00');
        $deadline = Reservation::computeDeadline($created, CarbonImmutable::parse('2026-12-01'));

        $this->assertSame('2026-11-20 10:00', $deadline->format('Y-m-d H:i'));
    }

    public function test_batas_bayar_2_jam_bila_check_in_kurang_dari_24_jam(): void
    {
        $created = CarbonImmutable::parse('2026-11-19 22:00');
        $deadline = Reservation::computeDeadline($created, CarbonImmutable::parse('2026-11-20')); // check-in 14.00 besok

        $this->assertSame('2026-11-20 00:00', $deadline->format('Y-m-d H:i'));
    }

    public function test_transition_to_menulis_log_dan_menolak_transisi_ilegal(): void
    {
        $this->seed([FacilitySeeder::class, BranchSeeder::class]);
        $user = User::factory()->create();
        $reservation = $this->makeReservation($user);

        $reservation->transitionTo(S::PendingPayment, $user, 'created');
        $reservation->transitionTo(S::Paid, null, 'gateway callback');

        $this->assertNotNull($reservation->paid_at);
        $this->assertSame(['pending_payment', 'paid'], $reservation->statusLogs()->pluck('to_status')->map->value->all());

        $this->expectException(InvalidStatusTransitionException::class);
        $reservation->transitionTo(S::Expired); // Paid -> Expired tidak diizinkan
    }

    public function test_kode_reservasi_berurutan_per_cabang_per_hari(): void
    {
        $this->seed([FacilitySeeder::class, BranchSeeder::class]);
        $user = User::factory()->create();
        $first = $this->makeReservation($user);
        $first->transitionTo(S::PendingPayment);

        $date = CarbonImmutable::parse('2026-11-19');
        $this->assertSame('NSH-JMB-20261119-0002', Reservation::generateCode($first->branch, $date));
    }

    private function makeReservation(User $user): Reservation
    {
        $branch = Branch::where('code', 'JMB')->first();

        return new Reservation([
            'code' => Reservation::generateCode($branch, CarbonImmutable::parse('2026-11-19')),
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-03',
            'nights' => 2,
            'total_guests' => 2,
            'guest_name' => $user->name,
            'guest_phone' => '0812',
            'subtotal' => 2400000,
            'tax_rate' => 21,
            'tax_amount' => 504000,
            'total_amount' => 2904000,
            'payment_deadline' => now()->addDay(),
        ]);
    }
}
