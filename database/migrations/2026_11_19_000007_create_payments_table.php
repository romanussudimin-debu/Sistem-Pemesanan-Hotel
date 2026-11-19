<?php

// database/migrations/2026_11_19_000007_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu reservasi dapat memiliki beberapa percobaan pembayaran; maksimal satu yang approved.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);                 // App\Enums\PaymentMethod
            $table->string('status', 20);                 // App\Enums\PaymentStatus
            $table->decimal('amount', 12, 2);
            $table->string('gateway_ref', 64)->nullable()->unique();
            $table->string('proof_path')->nullable();     // storage privat (NFR-04)
            $table->string('sender_name', 100)->nullable();
            $table->string('sender_bank', 50)->nullable();
            $table->date('transfer_date')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);      // antrean verifikasi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
