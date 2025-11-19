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
        Schema::create('transport_invoices', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2);
            $table->decimal('vat', 10, 2);
            $table->decimal('payable', 10, 2);
            $table->string('cus_name')->nullable();
            $table->string('cus_email');
            $table->string('cus_phone')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_method',5)->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('tax_ref')->nullable();
            $table->string('currency')->default('USD ');
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_invoices');
    }
};
