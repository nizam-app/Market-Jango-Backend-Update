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
        Schema::create('chats', function (Blueprint $table) {
            $table->string('message', 50);
            $table->string('message_type', 50);
            $table->boolean('is_read')->default(false);
            $table->foreignId('sender_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
