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
        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_issued'); // Tanggal penerbitan invoice
            $table->dateTime('due_date'); // Tanggal jatuh tempo
            $table->string('invoice', 60)->index(); // Nomor invoice yang diindeks
            $table->string('description', 160); // Deskripsi tagihan
            $table->decimal('bill_amount', 15, 2); // Jumlah tagihan, menggunakan decimal untuk akurasi
            $table->decimal('payment_amount', 15, 2); // Jumlah pembayaran yang diterima, menggunakan decimal
            $table->integer('payment_status'); // Status pembayaran, misalnya: 1 = belum dibayar, 2 = sebagian dibayar, 3 = lunas
            $table->integer('payment_nth'); // Urutan pembayaran (misalnya pembayaran ke-1, ke-2, dll.)
            $table->string('finance_type')->collect('Payable', 'Receivable');

            // Relasi dengan tabel contacts (misalnya kontak pelanggan)
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            // Kolom untuk account_code, yang mengacu pada chart_of_accounts
            $table->string('account_code', 10)->index(); // Mengacu pada akun terkait, diindeks
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finances');
    }
};
