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
        $vendorIds = DB::table('vendors')->pluck('id')->toArray();
        $categoryIds = DB::table('categories')->pluck('id')->toArray();
        $colors = ['red', 'green', 'blue', 'yellow', 'black', 'white'];
        $sizes  = ['S', 'M', 'L', 'XL', 'XXL'];
        for ($i = 1; $i <= 100; $i++) {
            DB::table('products')->insert([
                'name' => $faker->unique()->word,
                'description' => $faker->sentence,
                'regular_price' => $faker->numberBetween(100, 1000),
                'sell_price' => $faker->numberBetween(50, 999),
                'star' => $faker->numberBetween(0, 5),
                'image' => $faker->imageUrl(400, 400, 'food'),
                'remark' => $faker->randomElement(['Top', 'New']),
                'color' => json_encode($faker->randomElements($colors, rand(1, 3))),
                'size' => json_encode($faker->randomElements($sizes, rand(1, 3))),
                'vendor_id' => 1,
                'category_id' => $faker->randomElement([1,2]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
