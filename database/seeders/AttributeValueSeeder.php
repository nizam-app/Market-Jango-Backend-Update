<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AttributeValueSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // get product attributes
        $attributes = DB::table('product_attributes')->pluck('id', 'name');
        $vendorIds = DB::table('vendors')->pluck('id')->toArray();

        foreach ($attributes as $name => $id) {
            // every attribute for 5 random values store
            for ($i = 0; $i < 5; $i++) {
                DB::table('attribute_values')->insert([
                    'name' => $faker->unique()->word,
                    'product_attribute_id' => $id,
                    'vendor_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
