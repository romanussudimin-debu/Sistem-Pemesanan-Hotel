<?php

// database/migrations/2026_11_19_000006_create_reservations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();                     // FR-11
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');                                // tidak dihitung sebagai malam
            $table->unsignedTinyInteger('nights');
            $table->unsignedTinyInteger('total_guests');
            $table->string('guest_name', 100);
            $table->string('guest_phone', 20);
            $table->text('special_request')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_rate', 5, 2);                        // snapshot BR-08
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('status', 30);                             // App\Enums\ReservationStatus
            $table->dateTime('payment_deadline');                     // BR-09, BR-20
            $table->unsignedTinyInteger('rejection_count')->default(0); // BR-11
            $table->timestamp('paid_at')->nullable()->index();        // dasar laporan pendapatan
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);      // BR-14
            $table->string('refund_status', 20)->default('none');     // App\Enums\RefundStatus
            $table->timestamps();

            $table->index(['status', 'payment_deadline']);            // scheduler kedaluwarsa
            $table->index(['branch_id', 'check_in']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete(); // snapshot (denormalisasi sengaja)
            $table->unsignedTinyInteger('guests');
            $table->json('rate_breakdown');                           // snapshot tarif per malam (BR-07)
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        // Buku besar inventaris: 1 baris = 1 kamar terpakai pada 1 malam.
        // UNIQUE(room_id, stay_date) adalah lapis ke-3 anti-overbooking (Tahap 3, 3.3.3).
        Schema::create('room_nights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('stay_date')->index();

            $table->unique(['room_id', 'stay_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_nights');
        Schema::dropIfExists('reservation_rooms');
        Schema::dropIfExists('reservations');
    }
};
