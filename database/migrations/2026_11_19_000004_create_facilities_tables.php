<?php

// database/migrations/2026_11_19_000004_create_facilities_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name_id', 50);
            $table->string('name_en', 50);
            $table->string('icon', 50)->nullable(); // nama ikon Heroicons
            $table->timestamps();
        });

        // Pivot M:N tipe kamar <-> fasilitas
        Schema::create('facility_room_type', function (Blueprint $table) {
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['facility_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_room_type');
        Schema::dropIfExists('facilities');
    }
};
