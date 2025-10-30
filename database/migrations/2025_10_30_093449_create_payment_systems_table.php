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
        Schema::create('payment_systems', function (Blueprint $table) {
            $table->id();
            $table->string('public_key');
            $table->string('secret_key');
            $table->string('currency')->default('USD');
            $table->string('init_url')->default('https://api.flutterwave.com/v3/payments');
            $table->string('success_url')->nullable();
            $table->string('fail_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->string('ipn_url')->nullable();
            $table->string('title')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_systems');
    }
};
