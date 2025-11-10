<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            //LocationSeeder::class,
//            RouteSeeder::class,
            UserSeeder::class,
//            CategorySeeder::class,
//            ProductAttributeSeeder::class,
//            AttributeValueSeeder::class,
//            ProductSeeder::class,
            PermissionTableSeeder::class
        ]);
    }
}
