<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();
            $table->foreignId('user_id')  // who made the stock change
                  ->constrained('users')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();
            $table->integer('change'); // positive or negative change
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};