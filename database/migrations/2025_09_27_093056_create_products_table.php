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
            $table->string('previous_price', 50);
            $table->string('current_price', 50);
            $table->string('image', 200);
            $table->foreignId('vendor_id')->constrained('vendors')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
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
