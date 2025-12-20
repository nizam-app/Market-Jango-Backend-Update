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
        Schema::create('location_routes', function (Blueprint $table) {
 $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('start_point_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('end_point_id')->constrained('locations')->onDelete('cascade');
            $table->decimal('radius_km', 5, 2)->default(2);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_routes');
    }
};
