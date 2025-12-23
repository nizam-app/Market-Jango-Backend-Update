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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('address', 50);
            $table->string('business_name', 100);
            $table->time('open_time')->default('09:00');
            $table->time('close_time')->default('18:00');
            $table->double('avg_rating',3, 1)->default(0);
            $table->decimal('longitude', 10,6)->nullable();
            $table->decimal('latitude', 10,6)->nullable();
            $table->enum('business_type', ['Restaurant','Grocery','Pharmacy','Electronics','Clothing','Hardware'])->default('Restaurant');
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('cover_image',200)->nullable();
            $table->string('public_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
