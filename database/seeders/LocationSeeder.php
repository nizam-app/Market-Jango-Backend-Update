<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Location::insert([
            ['name'=>'Dhaka'],
            ['name'=>'Narsingdi'],
            ['name'=>'Brahmanbaria'],
            ['name'=>'Gazipur'],
            ['name'=>'Tangail'],
            ['name'=>'Sylhet'],
            ['name'=>'Moulvibazar'],
            ['name'=>'Habiganj'],
            ['name'=>'Sunamganj'],
            ['name'=>'Comilla'],
            ['name'=>'Chittagong'],
            ['name'=>'Coxs Bazar'],
            ['name'=>'Feni'],
            ['name'=>'Noakhali'],
            ['name'=>'Rangamati'],
            ['name'=>'Khulna'],
            ['name'=>'Jessore'],
            ['name'=>'Satkhira'],
            ['name'=>'Bagerhat'],
            ['name'=>'Jhenaidah'],
            ['name'=>'Barisal'],
            ['name'=>'Patuakhali'],
            ['name'=>'Bhola'],
            ['name'=>'Jhalokathi'],
            ['name'=>'Pirojpur'],
            ['name'=>'Rangpur'],
            ['name'=>'Dinajpur'],
            ['name'=>'Thakurgaon'],
            ['name'=>'Lalmonirhat'],
            ['name'=>'Kurigram'],
            ['name'=>'Rajshahi'],
            ['name'=>'Natore'],
            ['name'=>'Bogra'],
            ['name'=>'Pabna'],
            ['name'=>'Naogaon'],
            ['name'=>'Mymensingh'],
            ['name'=>'Jamalpur'],
            ['name'=>'Netrokona'],
            ['name'=>'Sherpur'],
            ['name'=>'Chittagong Hill Tracts'],
            ['name'=>'Bandarban'],
            ['name'=>'Khagrachhari'],
            ['name'=>'Narail'],
            ['name'=>'Sirajganj'],
            ['name'=>'Joypurhat'],
            ['name'=>'Munshiganj'],
            ['name'=>'Narayanganj'],
            ['name'=>'Maulvi Bazar'],
            ['name'=>'Chandpur'],
            ['name'=>'Nilphamari'],
            ['name'=>'Panchagarh']
        ]);
    }
}
