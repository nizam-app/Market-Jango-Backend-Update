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
        Schema::create('delivery_routes', function (Blueprint $table) {
$table->id();

      $table->foreignId('from_zone_id')
        ->constrained('zones')
        ->cascadeOnDelete()
        ->cascadeOnUpdate();

      $table->foreignId('to_zone_id')
        ->constrained('zones')
        ->cascadeOnDelete()
        ->cascadeOnUpdate();

      // vendor_id NULL => global/default route pricing
      // vendor_id NOT NULL => vendor-specific override
      $table->foreignId('vendor_id')
        ->nullable()
        ->constrained('vendors')
        ->nullOnDelete()
        ->cascadeOnUpdate();

      $table->decimal('base_charge', 10, 2)->default(0.00);
      $table->decimal('per_km_charge', 10, 2)->nullable();
      $table->decimal('min_charge', 10, 2)->nullable();
      $table->decimal('max_charge', 10, 2)->nullable();

      $table->enum('status', ['Active', 'Inactive'])->default('Active');
      $table->timestamps();

      // One unique rule per vendor override (or per global)
      $table->unique(['from_zone_id', 'to_zone_id', 'vendor_id'], 'delivery_routes_unique_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
    }
};
