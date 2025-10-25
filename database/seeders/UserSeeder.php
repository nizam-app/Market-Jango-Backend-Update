<?php

namespace Database\Seeders;

use App\Helpers\ResponseHelper;
use App\Models\Buyer;
use App\Models\Driver;
use App\Models\Transport;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $userTypes = ['buyer', 'vendor', 'driver', 'transport'];
        foreach ($userTypes as $type) {
            for ($i = 1; $i <= 5; $i++) {
                // Status set according to user type
                $status = in_array($type, ['buyer', 'transport']) ? 'Approved' : 'Pending';

                $id = DB::table('users')->insertGetId([
                    'user_type' => $type,
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'phone' => $faker->unique()->numerify('01#########'),
                    'otp' => null,
                    'phone_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'language' => $faker->randomElement(['English','Français','Русский','Tiếng Việt']),
                    'image' => $faker->imageUrl(200, 200, 'people'),
                    'status' => 'Approved',
                    'expires_at' => null,
                    'remember_token' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($type === 'buyer') {
                    Buyer::create(['user_id' => $id]);
                } elseif ($type === 'transport') {
                    Transport::create(['user_id' => $id]);
                } elseif ($type === 'vendor') {
                    Vendor::create([
                        'country' => 'Bangladesh',
                        'business_name' => $faker->name,
                        'business_type' => 'Restaurant',
                        'address' => substr($faker->address, 0, 50),
                        'user_id' => $id,
                    ]);
                } elseif ($type === 'driver') {
                    Driver::create([
                        'car_name' => $faker->randomElement(['Toyota', 'Honda', 'Nissan', 'Hyundai', 'Ford', 'BMW', 'Mercedes']),
                        'car_model' => $faker->word,
                        'location' => $faker->unique()->city,
                        'price' => $faker->numberBetween(100, 10000),
                        'user_id' => $id,
                        'route_id' => $faker->numberBetween(1, 20),
                    ]);
                }
            }
        }
    }
}
