<?php

// database/migrations/2026_11_19_000002_create_room_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description_id')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedTinyInteger('capacity');           // BR-04
            $table->unsignedSmallInteger('size_sqm')->nullable();
            $table->decimal('base_price', 12, 2);              // BR-07
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'slug']);
        });

        Schema::create('room_type_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('sort_order')->default(0); // 0 = foto utama
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_images');
        Schema::dropIfExists('room_types');
    }
};
