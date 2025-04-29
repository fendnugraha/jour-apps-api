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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); // Tipe data otomatis unsignedBigInteger
            $table->string('code', 3)->unique(); // Kode warehouse
            $table->string('name', 30)->unique(); // Nama warehouse
            $table->string('address'); // Alamat warehouse
            $table->foreignId('chart_of_account_id'); // Relasi ke chart_of_accounts
            $table->integer('status')->default(1); // Status dengan nilai default 1
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
