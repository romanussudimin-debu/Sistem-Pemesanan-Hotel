<?php

// tests/Feature/CancellationTest.php

namespace Tests\Feature;

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\User;
use App\Services\CancellationPolicy;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seedHotel();
        $this->user = User::factory()->create();
    }

    private function paidBooking(int $daysAhead)
    {
        $r = $this->book($this->user, $this->roomType('Superior Garden'), $daysAhead, 1);
        DB::transaction(fn () => $r->transitionTo(ReservationStatus::Paid));

        return $r;
    }

    /** BR-14: ≥7 hari 100%, 3–6 hari 50%, <3 hari 0% */
    public function test_persentase_refund_sesuai_kebijakan(): void
    {
        $policy = app(CancellationPolicy::class);

        $this->assertSame(100, $policy->refundPercentage($this->paidBooking(7)));
        $this->assertSame(50, $policy->refundPercentage($this->paidBooking(6)));
        $this->assertSame(50, $policy->refundPercentage($this->paidBooking(3)));
        $this->assertSame(0, $policy->refundPercentage($this->paidBooking(2)));
    }

    public function test_pembatalan_lunas_mencatat_refund_dan_melepas_kamar(): void
    {
        $r = $this->paidBooking(4);
        $this->actingAs($this->user)->post(route('reservations.cancel', $r), ['reason' => 'Batal liburan'])
            ->assertRedirect(route('reservations.show', $r));

        $fresh = $r->fresh();
        $this->assertSame(ReservationStatus::Cancelled, $fresh->status);
        $this->assertEquals(round((float) $r->total_amount / 2), (float) $fresh->refund_amount);
        $this->assertSame(RefundStatus::Pending, $fresh->refund_status);
        $this->assertSame(0, $fresh->nights()->count());
    }

    public function test_pembatalan_sebelum_bayar_tanpa_refund(): void
    {
        $r = $this->book($this->user, $this->roomType('Superior Garden'), 20, 1);
        app(ReservationService::class)->cancel($r, $this->user);

        $this->assertSame(RefundStatus::None, $r->fresh()->refund_status);
        $this->assertEquals(0, (float) $r->fresh()->refund_amount);
    }

    public function test_status_check_in_tidak_dapat_dibatalkan(): void
    {
        $r = $this->paidBooking(0);
        DB::transaction(fn () => $r->transitionTo(ReservationStatus::CheckedIn));

        $this->actingAs($this->user)->post(route('reservations.cancel', $r))->assertForbidden(); // BR-15
    }

    public function test_admin_wajib_mengisi_alasan(): void
    {
        $r = $this->paidBooking(10);

        $this->actingAs($this->admin())->post(route('admin.reservations.cancel', $r), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }
}
