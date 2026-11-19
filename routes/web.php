<?php

// routes/web.php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MockPayController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
| Publik (P-01 s.d. P-04)
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
Route::get('/search', SearchController::class)->name('search');
Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
Route::get('/branches/{branch:slug}', [BranchController::class, 'show'])->name('branches.show');
// Slug tipe kamar hanya unik per cabang, jadi rute memakai scoped binding cabang → tipe kamar
Route::get('/branches/{branch:slug}/rooms/{roomType:slug}', [RoomTypeController::class, 'show'])
    ->scopeBindings()->name('rooms.show');

// Webhook gateway (server ke server, tanpa CSRF, diamankan HMAC)
Route::post('/payments/mockpay/callback', PaymentCallbackController::class)
    ->middleware('throttle:60,1')->name('payments.callback');

/*
| Pelanggan (login + email terverifikasi)
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    Route::get('/reservations/{reservation}/voucher', [ReservationController::class, 'voucher'])->name('reservations.voucher');

    Route::get('/reservations/{reservation}/payment', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/reservations/{reservation}/payment/gateway', [PaymentController::class, 'gateway'])->name('payments.gateway');
    Route::post('/reservations/{reservation}/payment/proof', [PaymentController::class, 'proof'])->name('payments.proof');
    Route::get('/reservations/{reservation}/payment/status', [PaymentController::class, 'status'])->name('payments.status');

    Route::get('/mockpay/{ref}', [MockPayController::class, 'show'])->name('mockpay.show');
    Route::post('/mockpay/{ref}', [MockPayController::class, 'complete'])->name('mockpay.complete');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
| Admin (A-01 s.d. A-10) — FR-03: hanya peran admin
*/
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/reservations', [Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [Admin\ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{reservation}/check-in', [Admin\ReservationController::class, 'checkIn'])->name('reservations.check-in');
    Route::post('/reservations/{reservation}/check-out', [Admin\ReservationController::class, 'checkOut'])->name('reservations.check-out');
    Route::post('/reservations/{reservation}/cancel', [Admin\ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::get('/payments', [Admin\PaymentVerificationController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [Admin\PaymentVerificationController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/proof', [Admin\PaymentVerificationController::class, 'proof'])->name('payments.proof');
    Route::post('/payments/{payment}/approve', [Admin\PaymentVerificationController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [Admin\PaymentVerificationController::class, 'reject'])->name('payments.reject');

    Route::resource('branches', Admin\BranchController::class)->except('show');
    Route::resource('room-types', Admin\RoomTypeController::class)->except('show');
    Route::delete('/room-types/{room_type}/images/{image}', [Admin\RoomTypeController::class, 'destroyImage'])->name('room-types.images.destroy');
    Route::resource('rooms', Admin\RoomController::class)->except('show');
    Route::resource('facilities', Admin\FacilityController::class)->except('show');
    Route::resource('room-rates', Admin\RoomRateController::class)->except('show');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/print', [Admin\ReportController::class, 'print'])->name('reports.print');
    Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
});

require __DIR__.'/auth.php';
