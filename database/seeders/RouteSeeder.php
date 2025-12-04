<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Route::query()->delete();
        Route::insert([
            ['name' => 'Dhaka to Sylhet'],
            ['name' => 'Chittagong to Barisal'],
            ['name' => 'Sylhet to Dhaka'],
            ['name' => 'Sylhet to Coxs Bazar'],
            ['name' => 'Khulna to Dhaka'],
            ['name' => 'Rajshahi to Dhaka'],
            ['name' => 'Barisal to Dhaka'],
            ['name' => 'Rangpur to Dhaka'],
            ['name' => 'Jessore to Dhaka'],
            ['name' => 'Comilla to Dhaka'],
            ['name' => 'Coxs Bazar to Dhaka'],
        ]);
    }
}
