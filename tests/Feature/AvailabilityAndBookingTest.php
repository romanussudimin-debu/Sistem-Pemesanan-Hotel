<?php

// tests/Feature/AvailabilityAndBookingTest.php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomNight;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

/** Kasus uji ketersediaan Tahap 3, 3.3.5 (TC-AV-01 s.d. TC-AV-08). */
class AvailabilityAndBookingTest extends TestCase
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

    public function test_reservasi_menyimpan_kamar_malam_harga_dan_status_awal(): void
    {
        $type = $this->roomType('Deluxe Garden View');
        $r = $this->book($this->user, $type, 10, 2, [2]);

        $this->assertSame(ReservationStatus::PendingPayment, $r->status);
        $this->assertMatchesRegularExpression('/^NSH-JMB-\d{8}-0001$/', $r->code);
        $this->assertSame(2, $r->nights()->count());
        $this->assertEquals(2400000 * 1.21, (float) $r->total_amount); // dengan asumsi tanggal di luar musim
        $this->assertSame(1, $r->statusLogs()->count());
    }

    public function test_tc_av_01_pemesanan_berurutan_back_to_back_diizinkan(): void
    {
        $type = $this->roomType('Pool Villa');
        $this->limitRooms($type, 1);

        $this->book($this->user, $type, 10, 2);           // malam 10, 11
        $second = $this->book($this->user, $type, 12, 2);  // check-in = check-out sebelumnya

        $this->assertSame(ReservationStatus::PendingPayment, $second->status);
    }

    public function test_tc_av_02_irisan_sebagian_ditolak(): void
    {
        $type = $this->roomType('Pool Villa');
        $this->limitRooms($type, 1);
        $this->book($this->user, $type, 10, 2);

        $this->expectException(RoomNotAvailableException::class);
        $this->book($this->user, $type, 11, 2);
    }

    public function test_tc_av_03_rentang_membungkus_ditolak(): void
    {
        $type = $this->roomType('Pool Villa');
        $this->limitRooms($type, 1);
        $this->book($this->user, $type, 11, 1);

        $this->expectException(RoomNotAvailableException::class);
        $this->book($this->user, $type, 10, 4);
    }

    public function test_tc_av_04_kamar_perawatan_tidak_dihitung(): void
    {
        $type = $this->roomType('Superior Garden'); // seeder: kamar 102 berstatus maintenance
        $in = CarbonImmutable::today()->addDays(5);

        $this->assertSame(5, app(AvailabilityService::class)->availableCount($type, $in, $in->addDay()));
    }

    public function test_tc_av_05_kedaluwarsa_melepas_kamar(): void
    {
        $type = $this->roomType('Pool Villa');
        $this->limitRooms($type, 1);
        $this->book($this->user, $type, 10, 2);

        $this->travel(25)->hours();
        $this->artisan('reservations:expire')->assertSuccessful();
        $this->travelBack();

        $this->assertSame(ReservationStatus::PendingPayment, $this->book($this->user, $type, 10, 2)->status);
    }

    public function test_tc_av_06_pembatalan_melepas_kamar(): void
    {
        $type = $this->roomType('Pool Villa');
        $this->limitRooms($type, 1);
        $first = $this->book($this->user, $type, 10, 2);
        app(ReservationService::class)->cancel($first, $this->user);

        $this->assertSame(0, $first->nights()->count());
        $this->assertSame(ReservationStatus::PendingPayment, $this->book($this->user, $type, 10, 2)->status);
    }

    public function test_tc_av_07_lapis_tiga_indeks_unik_menolak_meski_cek_ketersediaan_usang(): void
    {
        $type = $this->roomType('Pool Villa');
        $this->limitRooms($type, 1);
        $this->book($this->user, $type, 10, 2);

        // Simulasikan bug/race di lapis 2: AvailabilityService "lupa" memeriksa room_nights
        $this->mock(AvailabilityService::class, fn ($m) => $m->shouldReceive('freeRooms')
            ->andReturn(Room::where('room_type_id', $type->id)->bookable()->orderBy('number')));
        $this->app->forgetInstance(ReservationService::class);

        try {
            $this->book($this->user, $type, 11, 1);
            $this->fail('Overbooking seharusnya ditolak oleh indeks unik');
        } catch (RoomNotAvailableException) {
            $this->assertSame(1, Reservation::count()); // transaksi di-rollback seluruhnya
        }
    }

    public function test_tc_av_08_indeks_unik_room_nights(): void
    {
        $r = $this->book($this->user, $this->roomType('Pool Villa'), 10, 1);
        $night = $r->nights()->first();

        $this->expectException(UniqueConstraintViolationException::class);
        RoomNight::create(['reservation_room_id' => $night->reservation_room_id, 'room_id' => $night->room_id, 'stay_date' => $night->stay_date]);
    }

    public function test_pemesanan_beberapa_kamar_mengalokasikan_kamar_berbeda(): void
    {
        $r = $this->book($this->user, $this->roomType('Deluxe Garden View'), 10, 2, [2, 1, 2]);

        $this->assertSame(3, $r->rooms()->distinct()->count('room_id'));
        $this->assertSame(5, $r->total_guests);
        $this->assertSame(6, $r->nights()->count());
    }

    public function test_tarif_musiman_menimpa_tarif_dasar(): void
    {
        $type = $this->roomType('Deluxe Garden View');
        $year = today()->year;
        // 19–21 Des: 1 malam tarif dasar (19) + 1 malam Natal (20)
        $quote = app(PricingService::class)->quote($type, CarbonImmutable::parse("{$year}-12-19"), CarbonImmutable::parse("{$year}-12-21"));

        $this->assertSame(['base', 'seasonal'], array_column($quote->nights, 'source'));
        $this->assertEquals(1200000 + 1680000, $quote->perRoom);
        $this->assertEquals(round(2880000 * 0.21), $quote->tax);
    }
}
