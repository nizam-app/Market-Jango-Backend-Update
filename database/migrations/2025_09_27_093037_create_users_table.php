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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable();
            $table->string('user_image', 200)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('token')->nullable();
            $table->string('password')->nullable();
            $table->string('otp', 8)->nullable();
            $table->enum('user_type', ['Buyer','Vendor','Driver','Transport'])->default('Buyer');
            $table->enum('language', ['English','Français','Русский','Tiếng Việt'])->default('English');
            $table->enum('status', ['Pending','Approved','Rejected'])->default('Pending');
            $table->timestamp('phone_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
