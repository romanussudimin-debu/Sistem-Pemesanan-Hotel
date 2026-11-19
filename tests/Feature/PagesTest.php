<?php

// tests/Feature/PagesTest.php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

/** Smoke test: setiap halaman dapat dirender untuk perannya, dalam kedua bahasa. */
class PagesTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private User $customer;

    private User $admin;

    private Reservation $pending;

    private Reservation $paid;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        $this->seedHotel();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
        $this->admin = $this->admin();

        $this->pending = $this->book($this->customer, $this->roomType('Deluxe Garden View'), 10, 2, [2]);
        $this->paid = $this->book($this->customer, $this->roomType('Jungle Villa'), 0, 2);
        DB::transaction(fn () => $this->paid->transitionTo(ReservationStatus::Paid));

        $proofFor = $this->book($this->customer, $this->roomType('Superior Room'), 5, 1);
        app(PaymentService::class)->submitProof($proofFor, UploadedFile::fake()->image('b.png'),
            ['sender_name' => 'Budi', 'sender_bank' => 'XYZ', 'transfer_date' => today()->toDateString()], $this->customer);
    }

    public static function locales(): array
    {
        return [['id'], ['en']];
    }

    #[DataProvider('locales')]
    public function test_halaman_publik(string $locale): void
    {
        $this->withSession(['locale' => $locale]);
        $branch = Branch::where('code', 'JMB')->first();
        $type = $this->roomType('Ocean Suite');
        $dates = ['check_in' => today()->addDays(3)->toDateString(), 'check_out' => today()->addDays(5)->toDateString()];

        $this->get(route('home'))->assertOk()->assertSee('Nusa Stay Jimbaran');
        $this->get(route('branches.index'))->assertOk();
        $this->get(route('branches.show', $branch->slug))->assertOk()->assertSee('Ocean Suite');
        $this->get(route('rooms.show', [$branch->slug, $type->slug] + $dates))->assertOk()->assertSee(money(2100000));
        $this->get(route('search', ['branch' => $branch->slug, 'guests' => 2, 'rooms' => 1] + $dates))->assertOk()->assertSee('Deluxe Garden View');
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    #[DataProvider('locales')]
    public function test_halaman_pelanggan(string $locale): void
    {
        $this->actingAs($this->customer)->withSession(['locale' => $locale]);
        $type = $this->roomType('Ocean Suite');

        $this->get(route('checkout.create', ['room_type' => $type->id, 'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(), 'rooms' => 2, 'guests' => 4]))->assertOk();
        $this->get(route('reservations.index'))->assertOk()->assertSee($this->pending->code);
        foreach (['all', 'active', 'pending', 'completed', 'cancelled'] as $tab) {
            $this->get(route('reservations.index', ['tab' => $tab]))->assertOk();
        }
        $this->get(route('reservations.show', $this->pending))->assertOk();
        $this->get(route('reservations.show', $this->paid))->assertOk();
        $this->get(route('payments.show', $this->pending))->assertOk()->assertSee('Bank ABC');
        $this->get(route('payments.status', $this->paid))->assertOk();
        $this->get(route('reservations.voucher', $this->paid))->assertOk()->assertSee($this->paid->code);
        $this->get(route('reservations.voucher', $this->pending))->assertForbidden(); // belum lunas
        $this->get(route('profile.edit'))->assertOk();

        $payment = app(PaymentService::class)->startGateway($this->pending);
        $this->get(route('mockpay.show', $payment->gateway_ref))->assertOk()->assertSee('MockPay');
    }

    #[DataProvider('locales')]
    public function test_halaman_admin(string $locale): void
    {
        $this->actingAs($this->admin)->withSession(['locale' => $locale]);
        $payment = Payment::where('status', 'submitted')->first();

        $pages = [
            route('admin.dashboard'), route('admin.dashboard', ['branch_id' => Branch::first()->id]),
            route('admin.reservations.index'), route('admin.reservations.index', ['status' => 'paid', 'q' => 'NSH']),
            route('admin.reservations.show', $this->paid),
            route('admin.payments.index'), route('admin.payments.index', ['status' => 'approved']), route('admin.payments.show', $payment),
            route('admin.branches.index'), route('admin.branches.create'), route('admin.branches.edit', Branch::first()),
            route('admin.room-types.index'), route('admin.room-types.create'), route('admin.room-types.edit', RoomType::first()),
            route('admin.rooms.index'), route('admin.rooms.create'), route('admin.rooms.edit', Room::first()),
            route('admin.facilities.index'), route('admin.facilities.create'),
            route('admin.room-rates.index'), route('admin.room-rates.create'), route('admin.room-rates.edit', RoomRate::first()),
        ];
        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['occupancy', 'revenue', 'reservations', 'top'] as $tab) {
            $this->get(route('admin.reports.index', ['tab' => $tab, 'group_by' => 'month']))->assertOk();
            $this->get(route('admin.reports.print', ['tab' => $tab]))->assertOk();
            $this->get(route('admin.reports.export', ['tab' => $tab]))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
        $this->get(route('admin.payments.proof', $payment))->assertOk();
    }

    public function test_pengalih_bahasa_menyimpan_pilihan_ke_profil(): void
    {
        $this->actingAs($this->customer)->from(route('home'))->get(route('locale.switch', 'en'))->assertRedirect(route('home'));

        $this->assertSame('en', $this->customer->fresh()->locale);
        $this->get(route('home'))->assertSee('Choose Our Branch');
        $this->get(route('locale.switch', 'id'));
        $this->get(route('home'))->assertSee('Pilih Cabang Kami');
        $this->get(route('locale.switch', 'fr'))->assertNotFound();
    }

    public function test_halaman_galat_khusus(): void
    {
        $this->actingAs($this->customer)->get(route('admin.dashboard'))->assertForbidden()->assertSee('403');
        $this->get('/tidak-ada')->assertNotFound()->assertSee('404');
    }
}
