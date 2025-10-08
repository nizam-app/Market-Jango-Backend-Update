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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('total',50)->nullable();
            $table->string('vat',50)->nullable();
            $table->string('payable',50)->nullable();
            $table->string('buyer_details',50)->nullable();
            $table->string('ship_details',50)->nullable();
            $table->string('tran_id',50)->nullable();
            $table->string('payment_status',50)->nullable();
            $table->enum('delivery_status',['packed', 'OnTheWay','Shipped','NotDelivery'])->nullable();
            $table->string('total',50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
