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
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->enum('gender', ['Male', 'Female'])->default('Male');
            $table->string('age', 100)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postcode', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('ship_name', 100)->nullable();
            $table->string('ship_email', 100)->nullable();
            $table->string('ship_location', 100)->nullable();
            $table->decimal('ship_latitude', 10,6)->nullable();
            $table->decimal('ship_longitude', 10,6)->nullable();
            $table->string('ship_country', 100)->nullable();
            $table->string('ship_phone', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->index('address');
            $table->index('location');
            $table->index('ship_name');
            $table->index('ship_phone');
            $table->index('ship_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyers');
    }
};
