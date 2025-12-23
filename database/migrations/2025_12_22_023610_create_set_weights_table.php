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
        Schema::create('set_weights', function (Blueprint $table) {
            $table->id();
            $table->decimal('max_weight', 8, 2)->comment('Maximum weight for this slab');
            $table->enum('weight_unit', ['kg', 'gram'])->default('kg');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('set_weights');
    }
};
