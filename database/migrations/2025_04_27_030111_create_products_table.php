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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique()->nullable();
            $table->string('name', 160)->unique();
            $table->decimal('cost', 10, 2);
            $table->decimal('price', 10, 2);
            $table->integer('sold')->default(0);
            $table->integer('init_stock')->default(0);
            $table->integer('end_stock')->default(0);
            $table->string('category', 60)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
