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
            ['name' => 'Dhaka to Chittagong'],
            ['name' => 'Dhaka to Khulna'],
            ['name' => 'Dhaka to Rajshahi'],
            ['name' => 'Dhaka to Rangpur'],
            ['name' => 'Dhaka to Barisal'],
            ['name' => 'Dhaka to Comilla'],
            ['name' => 'Dhaka to Coxs Bazar'],
            ['name' => 'Dhaka to Mymensingh'],
            ['name' => 'Dhaka to Jessore'],
            ['name' => 'Chittagong to Sylhet'],
            ['name' => 'Chittagong to Khulna'],
            ['name' => 'Chittagong to Coxs Bazar'],
            ['name' => 'Chittagong to Rajshahi'],
            ['name' => 'Chittagong to Rangpur'],
            ['name' => 'Chittagong to Barisal'],
            ['name' => 'Sylhet to Dhaka'],
            ['name' => 'Sylhet to Chittagong'],
            ['name' => 'Sylhet to Coxs Bazar'],
            ['name' => 'Sylhet to Rajshahi'],
            ['name' => 'Sylhet to Khulna'],
            ['name' => 'Khulna to Dhaka'],
            ['name' => 'Khulna to Chittagong'],
            ['name' => 'Khulna to Barisal'],
            ['name' => 'Khulna to Jessore'],
            ['name' => 'Rajshahi to Dhaka'],
            ['name' => 'Rajshahi to Sylhet'],
            ['name' => 'Rajshahi to Khulna'],
            ['name' => 'Rajshahi to Barisal'],
            ['name' => 'Rajshahi to Rangpur'],
            ['name' => 'Barisal to Dhaka'],
            ['name' => 'Barisal to Chittagong'],
            ['name' => 'Barisal to Khulna'],
            ['name' => 'Barisal to Rajshahi'],
            ['name' => 'Rangpur to Dhaka'],
            ['name' => 'Rangpur to Sylhet'],
            ['name' => 'Rangpur to Khulna'],
            ['name' => 'Rangpur to Rajshahi'],
            ['name' => 'Rangpur to Comilla'],
            ['name' => 'Jessore to Dhaka'],
            ['name' => 'Jessore to Khulna'],
            ['name' => 'Jessore to Barisal'],
            ['name' => 'Jessore to Chittagong'],
            ['name' => 'Comilla to Dhaka'],
            ['name' => 'Comilla to Chittagong'],
            ['name' => 'Comilla to Coxs Bazar'],
            ['name' => 'Coxs Bazar to Dhaka'],
            ['name' => 'Coxs Bazar to Chittagong'],
            ['name' => 'Coxs Bazar to Sylhet'],
        ]);
    }
}
