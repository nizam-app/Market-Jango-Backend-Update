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
            $table->enum('user_type', ['buyer','vendor','driver','transport','admin'])->default('buyer');
            $table->timestamp('last_active_at')->nullable();
            $table->string('name', 50)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('otp', 6)->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('language', ['en', 'fr', 'ru', 'vi'])->default('en');
            $table->string('image', 200)->nullable();
            $table->string('public_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['Pending','Approved','Rejected'])->default('Pending');
            $table->text('fcm_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            // invite flow er jonno
            $table->string('invite_token')->nullable()->unique();
            // first time password set/change flag (optional but useful)
            $table->boolean('must_change_password')->default(false);
            $table->rememberToken();
            $table->index('is_active');
            $table->index('user_type');
            $table->index('language');
            $table->index('status');
            $table->timestamps();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
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
