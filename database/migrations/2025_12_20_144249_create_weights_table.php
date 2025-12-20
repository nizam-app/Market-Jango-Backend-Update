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
        Schema::create('weights', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_weight', 8, 2)->comment('Minimum weight for this slab');
            $table->decimal('max_weight', 8, 2)->comment('Maximum weight for this slab');
            $table->enum('weight_unit', ['kg', 'gram'])->default('kg');
            $table->decimal('delivery_charge', 10, 2)->comment('Delivery charge for this weight range');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weights');
    }
};
