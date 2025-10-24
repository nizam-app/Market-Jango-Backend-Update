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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->text('description');
            $table->decimal('regular_price', 10, 2)->default(0.00);
            $table->decimal('sell_price', 10, 2)->default(0.00);
            $table->integer('discount')->default(0);
            $table->string('public_id')->nullable();
            $table->integer('star')->default(0);
            $table->string('image', 200);
            $table->json('color');
            $table->json('size');
            $table->enum('remark', ['Top', 'New'])->default('New');
            $table->boolean('is_active')->default(0)->comment('0 = No, 1 = Yes');
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            //index
            $table->index('is_active');
            $table->index('remark');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
