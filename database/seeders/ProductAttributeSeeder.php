<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $attributes = [
            'Size', 'Color', 'Weight', 'Material', 'Length', 'Width', 'Height',
            'Capacity', 'Voltage', 'Power', 'Speed', 'Memory', 'Storage',
            'Resolution', 'Warranty', 'Brand', 'Model', 'Type', 'Shape', 'Pattern'
        ];
        $vendorIds = DB::table('vendors')->pluck('id')->toArray();
        foreach ($attributes as $attribute) {
            DB::table('product_attributes')->insert([
                'name' => $attribute,
                'vendor_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
