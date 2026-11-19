<?php

// tests/Feature/AccessControlTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

/** FR-03 / NFR-03 dan validasi checkout (BR-01 s.d. BR-04). */
class AccessControlTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seedHotel();
    }

    public function test_pelanggan_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('reservations.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_pelanggan_tidak_bisa_mengakses_reservasi_orang_lain(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $r = $this->book($owner, $this->roomType('Ocean Suite'), 10, 1);

        $this->actingAs($other)->get(route('reservations.show', $r))->assertForbidden();
        $this->actingAs($other)->get(route('payments.show', $r))->assertForbidden();
        $this->actingAs($other)->post(route('reservations.cancel', $r))->assertForbidden();
        $this->actingAs($other)->post(route('payments.gateway', $r))->assertForbidden();
    }

    public function test_peran_tidak_bisa_disuntikkan_saat_registrasi(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup', 'email' => 'penyusup@contoh.test', 'role' => 'admin',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $this->assertSame('customer', User::where('email', 'penyusup@contoh.test')->first()->role->value);
    }

    public function test_validasi_checkout_lama_menginap_dan_kapasitas(): void
    {
        $user = User::factory()->create();
        $type = $this->roomType('Deluxe Garden View'); // kapasitas 2
        $in = today()->addDays(5);

        $this->actingAs($user)->post(route('checkout.store'), [
            'room_type_id' => $type->id,
            'check_in' => $in->toDateString(),
            'check_out' => $in->copy()->addDays(15)->toDateString(), // BR-02: maks 14 malam
            'guests' => [3],                                          // BR-04: melebihi kapasitas
            'guest_name' => 'Budi', 'guest_phone' => '+62 812 1111 2222', 'agree' => '1',
        ])->assertSessionHasErrors(['check_out', 'guests']);

        $this->actingAs($user)->post(route('checkout.store'), [
            'room_type_id' => $type->id,
            'check_in' => today()->subDay()->toDateString(), // BR-01: tidak boleh tanggal lampau
            'check_out' => $in->toDateString(),
            'guests' => [2], 'guest_name' => 'Budi', 'guest_phone' => '+62 812 1111 2222',
        ])->assertSessionHasErrors(['check_in', 'agree']);
    }

    public function test_checkout_berhasil_mengarah_ke_halaman_pembayaran(): void
    {
        $user = User::factory()->create();
        $in = today()->addDays(5);

        $this->actingAs($user)->post(route('checkout.store'), [
            'room_type_id' => $this->roomType('Ocean Suite')->id,
            'check_in' => $in->toDateString(),
            'check_out' => $in->copy()->addDays(2)->toDateString(),
            'guests' => [3], 'guest_name' => 'Budi', 'guest_phone' => '+62 812 1111 2222', 'agree' => '1',
        ])->assertRedirect(route('payments.show', $user->reservations()->first()));
    }
}
