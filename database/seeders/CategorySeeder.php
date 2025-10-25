<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Vendor IDs get from vendors table
        $vendorIds = DB::table('vendors')->pluck('id')->toArray();

        // Insert 20 categories
        for ($i = 1; $i <= 50; $i++) {
            DB::table('categories')->insert([
                'name' => $faker->unique()->word,
                'description' => $faker->sentence,
                'status' => $faker->randomElement(['Active', 'Inactive']),
                'vendor_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
