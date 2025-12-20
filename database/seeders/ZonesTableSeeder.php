<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Zone;
use Faker\Factory as Faker;

class ZonesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 1000; $i++) {
            Zone::create([
                'name' => 'Zone ' . $i, // unique name
                'center_latitude' => $faker->latitude(23.7, 24.0), // Bangladesh approx lat
                'center_longitude' => $faker->longitude(90.0, 90.5), // Bangladesh approx lng
                'radius_km' => 2, // default 2 KM
                'price' => $faker->numberBetween(10, 50), // random price
                'status' => 'Active',
            ]);
        }
    }
}
