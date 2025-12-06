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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->string('cus_name',50)->nullable();
            $table->string('cus_email',50)->nullable();
            $table->string('cus_phone',20)->nullable();
            $table->string('pickup_address',100)->nullable();
            $table->string('pickup_longitude',100)->nullable();
            $table->decimal('pickup_latitude', 10,6)->nullable();
            $table->string('payment_method',10)->nullable();
            $table->string('payment_proof_id',100)->nullable();
            $table->text('note')->nullable();
            $table->string('ship_address',100)->nullable();
            $table->decimal('ship_latitude', 10,6)->nullable();
            $table->string('ship_longitude',100)->nullable();
            $table->string('current_address',100)->nullable();
            $table->decimal('current_latitude', 10,6)->nullable();
            $table->decimal('current_longitude', 10,6)->nullable();
            $table->decimal('distance',10,2)->nullable();
            $table->integer('quantity')->nullable();
            $table->string('status',50)->nullable();
            $table->decimal('delivery_charge', 10, 2)->default(0.00);
            $table->decimal('total_pay', 10, 2)->default(0.00);
            $table->integer('sale_price')->nullable();
            $table->string('tran_id')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->index('tran_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
