<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Existing IDs
        $vendorIds = DB::table('vendors')->pluck('id')->toArray();
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        $attributeIds = DB::table('product_attributes')->pluck('id')->toArray();

        // Insert 50 products
        for ($i = 1; $i <= 50; $i++) {
            DB::table('products')->insert([
                'name' => $faker->unique()->word,
                'description' => $faker->sentence,
                'previous_price' => $faker->numberBetween(100, 1000),
                'current_price' => $faker->numberBetween(50, 999),
                'location' => $faker->city,
                'star' => $faker->numberBetween(0, 5),
                'image' => $faker->imageUrl(400, 400, 'food'),
                'remark' => $faker->randomElement(['Top', 'New']),
                'is_active' => $faker->boolean(70), // 70% chance active
                'product_attribute_id' => $faker->randomElement($attributeIds),
                'vendor_id' => $faker->randomElement($vendorIds),
                'category_id' => $faker->randomElement($categoryIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
