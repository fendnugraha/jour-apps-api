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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('acc_code', 30)->unique()->index();
            $table->string('acc_name', 100)->unique()->index();

            // Jika account_id adalah foreign key yang mengarah ke tabel accounts
            $table->foreignId('account_id')->constrained()->onDelete('cascade'); // Misalnya mengarah ke tabel accounts

            // Jika warehouse_id mengarah ke tabel warehouses, gunakan foreignId dan bukan integer
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null'); // Mengubah ke nullable dan set null jika warehouse dihapus

            $table->bigInteger('st_balance')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
