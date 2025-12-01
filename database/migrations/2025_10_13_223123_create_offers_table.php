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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 50)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('sale_price', 10,2)->nullable();
            $table->decimal('delivery_charge',10, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('image',200)->nullable();
            $table->string('public_id')->nullable();
            $table->text('note')->nullable();
            // je offer create korlo
            $table->foreignId('sender_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            // je ke pathano hoyche
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('color', 50)->nullable();
            $table->string('size', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
