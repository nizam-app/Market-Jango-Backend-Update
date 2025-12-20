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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
          
            $table->string('name', 50);
            // Zone center point
            $table->decimal('center_latitude', 10, 7);
            $table->decimal('center_longitude', 10, 7);

            // Radius in KM (e.g. 2 KM)
            $table->decimal('radius_km', 5, 2)->default(1);

            // Delivery price for this zone
            $table->decimal('price', 8, 2);

            $table->enum('status', ['Active', 'Inactive'])->default('Active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
