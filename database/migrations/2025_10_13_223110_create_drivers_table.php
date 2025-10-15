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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('car_name', 50);
            $table->string('car_model', 50);
            $table->string('location', 200);
            $table->string('price', 200);
            $table->integer('rating')->nullable()->default(0);
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('route_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
