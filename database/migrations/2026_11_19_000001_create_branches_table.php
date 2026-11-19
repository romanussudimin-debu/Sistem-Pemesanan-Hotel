<?php

// database/migrations/2026_11_19_000001_create_branches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();          // JMB, UBD, SMY (dipakai di kode reservasi)
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('city', 50);
            $table->string('address');
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->text('description_id')->nullable();
            $table->text('description_en')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('bank_name', 50);            // rekening tujuan transfer per cabang
            $table->string('bank_account_no', 30);
            $table->string('bank_account_name', 100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();                      // BR-19
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
