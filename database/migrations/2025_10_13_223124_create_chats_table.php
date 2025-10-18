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
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->string('sender_role');
            $table->string('receiver_role');
            $table->enum('type', ['text', 'image', 'video', 'file', 'audio', 'emoji', 'reply'])->default('text');
            $table->longText('message')->nullable();
            $table->string('media_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('reply_to')->nullable();
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
