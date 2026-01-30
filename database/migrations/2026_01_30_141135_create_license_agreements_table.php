<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('license_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('agreement_version'); // contoh: v1.0, v1.1
            $table->timestamp('accepted_at');
            $table->string('accepted_ip')->nullable();
            $table->string('accepted_user_agent')->nullable();
            $table->text('agreement_snapshot'); // isi agreement saat itu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_agreements');
    }
};
