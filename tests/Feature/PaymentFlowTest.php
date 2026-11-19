<?php

// tests/Feature/PaymentFlowTest.php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationNotification;
use App\Services\PaymentService;
use App\Services\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BookingFixtures;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use BookingFixtures, RefreshDatabase;

    private User $user;

    private User $admin;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        $this->seedHotel();
        $this->user = User::factory()->create();
        $this->admin = $this->admin();
        $this->reservation = $this->book($this->user, $this->roomType('Ocean Suite'), 15, 2, [2]);
    }

    private function callbackPayload(string $result, ?string $amount = null): array
    {
        $payment = app(PaymentService::class)->startGateway($this->reservation);
        $amount ??= number_format((float) $payment->amount, 2, '.', '');

        return [
            'gateway_ref' => $payment->gateway_ref,
            'amount' => $amount,
            'result' => $result,
            'signature' => PaymentService::signature($payment->gateway_ref, $amount, $result),
        ];
    }

    public function test_callback_gateway_sukses_melunasi_reservasi(): void
    {
        $this->postJson(route('payments.callback'), $this->callbackPayload('success'))
            ->assertOk()->assertJson(['ok' => true, 'status' => 'approved']);

        $this->assertSame(ReservationStatus::Paid, $this->reservation->fresh()->status);
        Notification::assertSentTo($this->user, ReservationNotification::class, fn ($n) => $n->event === 'paid');
    }

    public function test_callback_dengan_signature_atau_nominal_salah_ditolak(): void
    {
        $payload = $this->callbackPayload('success');

        $this->postJson(route('payments.callback'), ['signature' => 'palsu'] + $payload)->assertStatus(400);
        $this->postJson(route('payments.callback'), ['amount' => '1.00'] + $payload)->assertStatus(400);
        $this->assertSame(ReservationStatus::PendingPayment, $this->reservation->fresh()->status);
    }

    public function test_callback_idempoten(): void
    {
        $payload = $this->callbackPayload('success');
        $this->postJson(route('payments.callback'), $payload)->assertOk();
        $this->postJson(route('payments.callback'), $payload)->assertOk();

        $this->assertSame(2, $this->reservation->statusLogs()->count()); // created + paid, tidak dobel
    }

    public function test_callback_gagal_tidak_mengubah_status_reservasi(): void
    {
        $this->postJson(route('payments.callback'), $this->callbackPayload('failed'))->assertOk()->assertJson(['status' => 'failed']);

        $this->assertSame(ReservationStatus::PendingPayment, $this->reservation->fresh()->status);
    }

    public function test_alur_mockpay_dari_browser(): void
    {
        $this->actingAs($this->user)->post(route('payments.gateway', $this->reservation))->assertRedirect();
        $ref = $this->reservation->payments()->first()->gateway_ref;

        $this->actingAs($this->user)->post(route('mockpay.complete', $ref), ['result' => 'success'])
            ->assertRedirect(route('payments.status', $this->reservation));
        $this->assertSame(ReservationStatus::Paid, $this->reservation->fresh()->status);
    }

    public function test_unggah_bukti_transfer_lalu_admin_menyetujui(): void
    {
        $this->actingAs($this->user)->post(route('payments.proof', $this->reservation), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
            'sender_name' => 'Budi', 'sender_bank' => 'Bank XYZ', 'transfer_date' => today()->toDateString(),
        ])->assertRedirect(route('payments.status', $this->reservation));

        $payment = $this->reservation->payments()->first();
        $this->assertSame(PaymentStatus::Submitted, $payment->status);
        Storage::disk('local')->assertExists($payment->proof_path); // storage privat (NFR-04)
        $this->assertSame(ReservationStatus::AwaitingVerification, $this->reservation->fresh()->status);
        Notification::assertSentTo($this->admin, ReservationNotification::class, fn ($n) => $n->event === 'proof_submitted');

        $this->actingAs($this->admin)->post(route('admin.payments.approve', $payment))->assertRedirect();
        $this->assertSame(ReservationStatus::Paid, $this->reservation->fresh()->status);
        $this->assertSame($this->admin->id, $payment->fresh()->verified_by);
    }

    public function test_file_bukti_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->user)->post(route('payments.proof', $this->reservation), [
            'proof' => UploadedFile::fake()->create('virus.exe', 100),
            'sender_name' => 'Budi', 'sender_bank' => 'Bank XYZ', 'transfer_date' => today()->toDateString(),
        ])->assertSessionHasErrors('proof');

        $this->actingAs($this->user)->post(route('payments.proof', $this->reservation), [
            'proof' => UploadedFile::fake()->image('besar.jpg')->size(3000), // > 2 MB
            'sender_name' => 'Budi', 'sender_bank' => 'Bank XYZ', 'transfer_date' => today()->toDateString(),
        ])->assertSessionHasErrors('proof');
    }

    private function submitProof(): Payment
    {
        return app(PaymentService::class)->submitProof(
            $this->reservation->fresh(), UploadedFile::fake()->image('b.jpg'),
            ['sender_name' => 'Budi', 'sender_bank' => 'XYZ', 'transfer_date' => today()->toDateString()], $this->user,
        );
    }

    public function test_tiga_kali_ditolak_reservasi_dibatalkan(): void
    {
        $service = app(PaymentVerificationService::class);

        $service->reject($this->submitProof(), $this->admin, 'Buram');
        $this->assertSame(ReservationStatus::PendingPayment, $this->reservation->fresh()->status);
        $service->reject($this->submitProof(), $this->admin, 'Nominal kurang');
        $this->assertSame(0, $this->reservation->fresh()->remainingRejections());

        $service->reject($this->submitProof(), $this->admin, 'Rekening salah'); // BR-11: penolakan ke-3
        $fresh = $this->reservation->fresh();
        $this->assertSame(ReservationStatus::Cancelled, $fresh->status);
        $this->assertSame(0, $fresh->nights()->count());
    }

    public function test_br20_batas_bayar_diperpanjang_bila_ditolak_setelah_lewat(): void
    {
        $payment = $this->submitProof();
        $this->travel(30)->hours(); // admin terlambat memverifikasi; batas bayar asli sudah lewat

        app(PaymentVerificationService::class)->reject($payment, $this->admin, 'Buram');
        $fresh = $this->reservation->fresh();

        $this->assertSame(ReservationStatus::PendingPayment, $fresh->status);
        $this->assertTrue($fresh->payment_deadline->between(now()->addMinutes(119), now()->addMinutes(121)));
    }

    public function test_pembayaran_tidak_bisa_diverifikasi_dua_kali(): void
    {
        $payment = $this->submitProof();
        app(PaymentVerificationService::class)->approve($payment, $this->admin);

        $this->expectException(BusinessRuleException::class);
        app(PaymentVerificationService::class)->approve($payment, $this->admin); // UC-15 A3
    }

    public function test_menunggu_verifikasi_tidak_ikut_kedaluwarsa(): void
    {
        $this->submitProof();
        $this->travel(30)->hours();
        $this->artisan('reservations:expire');

        $this->assertSame(ReservationStatus::AwaitingVerification, $this->reservation->fresh()->status); // BR-10
    }

    public function test_callback_terlambat_setelah_kedaluwarsa_mengaktifkan_kembali_bila_kamar_masih_bebas(): void
    {
        $payload = $this->callbackPayload('success');
        $this->travel(25)->hours();
        $this->artisan('reservations:expire');
        $this->assertSame(ReservationStatus::Expired, $this->reservation->fresh()->status);

        $this->postJson(route('payments.callback'), $payload)->assertOk();

        $fresh = $this->reservation->fresh();
        $this->assertSame(ReservationStatus::Paid, $fresh->status);
        $this->assertSame(2, $fresh->nights()->count()); // kamar dialokasikan ulang
    }
}
