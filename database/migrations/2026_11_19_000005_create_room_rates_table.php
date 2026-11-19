<?php

// database/migrations/2026_11_19_000005_create_room_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tarif musiman yang menimpa base_price pada rentang tanggal tertentu (BR-07, FR-26)
        Schema::create('room_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50);
            $table->date('start_date'); // inklusif
            $table->date('end_date');   // inklusif
            $table->decimal('price', 12, 2);
            $table->timestamps();

            $table->index(['room_type_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_rates');
    }
};
