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
            $table->string('title', 50)->unique();
            $table->string('phone', 14)->nullable();
            $table->string('password');
            $table->string('otp', 8);
            $table->enum('role', ['Buyer','Vendor','Driver','Transport'])->default('Buyer');
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
