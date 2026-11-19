<?php

// tests/Feature/ReportServiceTest.php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\User;
use App\Services\ReportService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    public function test_okupansi_gabungan_adalah_jumlah_terjual_dibagi_jumlah_tersedia(): void
    {
        Notification::fake();
        $this->seedHotel();
        $user = User::factory()->create();

        $paid = $this->book($user, $this->roomType('Ocean Suite'), 3, 2);       // Jimbaran, 2 malam
        DB::transaction(fn () => $paid->transitionTo(ReservationStatus::Paid));
        $this->book($user, $this->roomType('Jungle Villa'), 3, 2);              // Ubud, belum bayar: tidak dihitung

        $from = CarbonImmutable::today()->addDays(3);
        $report = app(ReportService::class)->occupancy($from, $from->addDay()); // 2 hari

        $jimbaran = $report['rows']->firstWhere('branch', 'Nusa Stay Jimbaran');
        $this->assertSame(2, $jimbaran['sold']);
        $this->assertSame(22 * 2, $jimbaran['available']);
        $this->assertSame(2, $report['total']['sold']);
        // 60 kamar dikurangi 1 nonaktif (Seminyak 410) = 59 kamar × 2 hari
        $this->assertSame(59 * 2, $report['total']['available']);
        $this->assertEquals(round(2 / 118 * 100, 1), $report['total']['rate']);
    }

    public function test_pendapatan_bersih_mengurangi_refund(): void
    {
        Notification::fake();
        $this->seedHotel();
        $user = User::factory()->create();

        $r = $this->book($user, $this->roomType('Superior Garden'), 4, 1);
        DB::transaction(fn () => $r->transitionTo(ReservationStatus::Paid));
        app(ReservationService::class)->cancel($r->fresh(), $user); // 4 hari → refund 50%

        $row = app(ReportService::class)->revenue(today()->startOfMonth(), today()->endOfMonth())->first();
        $this->assertEquals((float) $r->total_amount, $row->gross);
        $this->assertEquals(round((float) $r->total_amount / 2), $row->net);
    }
}
