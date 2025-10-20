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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->default(1);
            $table->string('color',20);
            $table->string('size',20);
            $table->string('price',20);
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('status', ['active', 'checked_out'])->default('active');
            $table->index('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
