<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationRouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('location_route')->truncate();

        $location_routes = [
            // Route 1: Dhaka to Sylhet
            ['route_id'=>1, 'location_id'=>1, 'sequence'=>1],
            ['route_id'=>1, 'location_id'=>2, 'sequence'=>2],
            ['route_id'=>1, 'location_id'=>3, 'sequence'=>3],
            ['route_id'=>1, 'location_id'=>4, 'sequence'=>4],
            ['route_id'=>1, 'location_id'=>5, 'sequence'=>5],

            // Route 2: Dhaka to Chittagong
            ['route_id'=>2, 'location_id'=>6, 'sequence'=>1],
            ['route_id'=>2, 'location_id'=>7, 'sequence'=>2],
            ['route_id'=>2, 'location_id'=>8, 'sequence'=>3],
            ['route_id'=>2, 'location_id'=>9, 'sequence'=>4],
            ['route_id'=>2, 'location_id'=>10, 'sequence'=>5],

            // Route 3: Dhaka to Khulna
            ['route_id'=>3, 'location_id'=>11, 'sequence'=>1],
            ['route_id'=>3, 'location_id'=>12, 'sequence'=>2],
            ['route_id'=>3, 'location_id'=>13, 'sequence'=>3],
            ['route_id'=>3, 'location_id'=>14, 'sequence'=>4],
            ['route_id'=>3, 'location_id'=>15, 'sequence'=>5],

            // Route 4: Dhaka to Rajshahi
            ['route_id'=>4, 'location_id'=>16, 'sequence'=>1],
            ['route_id'=>4, 'location_id'=>17, 'sequence'=>2],
            ['route_id'=>4, 'location_id'=>18, 'sequence'=>3],
            ['route_id'=>4, 'location_id'=>19, 'sequence'=>4],
            ['route_id'=>4, 'location_id'=>20, 'sequence'=>5],

            // Route 5: Dhaka to Rangpur
            ['route_id'=>5, 'location_id'=>21, 'sequence'=>1],
            ['route_id'=>5, 'location_id'=>22, 'sequence'=>2],
            ['route_id'=>5, 'location_id'=>23, 'sequence'=>3],
            ['route_id'=>5, 'location_id'=>24, 'sequence'=>4],
            ['route_id'=>5, 'location_id'=>25, 'sequence'=>5],

            // Route 6: Dhaka to Barisal
            ['route_id'=>6, 'location_id'=>26, 'sequence'=>1],
            ['route_id'=>6, 'location_id'=>27, 'sequence'=>2],
            ['route_id'=>6, 'location_id'=>28, 'sequence'=>3],
            ['route_id'=>6, 'location_id'=>29, 'sequence'=>4],
            ['route_id'=>6, 'location_id'=>30, 'sequence'=>5],

            // Route 7: Dhaka to Comilla
            ['route_id'=>7, 'location_id'=>31, 'sequence'=>1],
            ['route_id'=>7, 'location_id'=>32, 'sequence'=>2],
            ['route_id'=>7, 'location_id'=>33, 'sequence'=>3],
            ['route_id'=>7, 'location_id'=>34, 'sequence'=>4],
            ['route_id'=>7, 'location_id'=>35, 'sequence'=>5],

            // Route 8: Dhaka to Coxs Bazar
            ['route_id'=>8, 'location_id'=>36, 'sequence'=>1],
            ['route_id'=>8, 'location_id'=>37, 'sequence'=>2],
            ['route_id'=>8, 'location_id'=>38, 'sequence'=>3],
            ['route_id'=>8, 'location_id'=>39, 'sequence'=>4],
            ['route_id'=>8, 'location_id'=>40, 'sequence'=>5],

            // Route 9: Dhaka to Mymensingh
            ['route_id'=>9, 'location_id'=>41, 'sequence'=>1],
            ['route_id'=>9, 'location_id'=>42, 'sequence'=>2],
            ['route_id'=>9, 'location_id'=>43, 'sequence'=>3],
            ['route_id'=>9, 'location_id'=>44, 'sequence'=>4],
            ['route_id'=>9, 'location_id'=>45, 'sequence'=>5],

            // Route 10: Dhaka to Jessore
            ['route_id'=>10, 'location_id'=>46, 'sequence'=>1],
            ['route_id'=>10, 'location_id'=>47, 'sequence'=>2],
            ['route_id'=>10, 'location_id'=>48, 'sequence'=>3],
            ['route_id'=>10, 'location_id'=>49, 'sequence'=>4],
            ['route_id'=>10, 'location_id'=>50, 'sequence'=>5],

            // Route 11: Chittagong to Sylhet
            ['route_id'=>11, 'location_id'=>51, 'sequence'=>1],
            ['route_id'=>11, 'location_id'=>52, 'sequence'=>2],
            ['route_id'=>11, 'location_id'=>53, 'sequence'=>3],
            ['route_id'=>11, 'location_id'=>54, 'sequence'=>4],
            ['route_id'=>11, 'location_id'=>55, 'sequence'=>5],

            // Route 12: Chittagong to Khulna
            ['route_id'=>12, 'location_id'=>56, 'sequence'=>1],
            ['route_id'=>12, 'location_id'=>57, 'sequence'=>2],
            ['route_id'=>12, 'location_id'=>58, 'sequence'=>3],
            ['route_id'=>12, 'location_id'=>59, 'sequence'=>4],
            ['route_id'=>12, 'location_id'=>60, 'sequence'=>5],

            // Route 13: Chittagong to Coxs Bazar
            ['route_id'=>13, 'location_id'=>61, 'sequence'=>1],
            ['route_id'=>13, 'location_id'=>62, 'sequence'=>2],
            ['route_id'=>13, 'location_id'=>63, 'sequence'=>3],
            ['route_id'=>13, 'location_id'=>64, 'sequence'=>4],
            ['route_id'=>13, 'location_id'=>65, 'sequence'=>5],

            // Route 14: Chittagong to Rajshahi
            ['route_id'=>14, 'location_id'=>66, 'sequence'=>1],
            ['route_id'=>14, 'location_id'=>67, 'sequence'=>2],
            ['route_id'=>14, 'location_id'=>68, 'sequence'=>3],
            ['route_id'=>14, 'location_id'=>69, 'sequence'=>4],
            ['route_id'=>14, 'location_id'=>70, 'sequence'=>5],

            // Route 15: Chittagong to Rangpur
            ['route_id'=>15, 'location_id'=>71, 'sequence'=>1],
            ['route_id'=>15, 'location_id'=>72, 'sequence'=>2],
            ['route_id'=>15, 'location_id'=>73, 'sequence'=>3],
            ['route_id'=>15, 'location_id'=>74, 'sequence'=>4],
            ['route_id'=>15, 'location_id'=>75, 'sequence'=>5],

            // Route 16: Chittagong to Barisal
            ['route_id'=>16, 'location_id'=>76, 'sequence'=>1],
            ['route_id'=>16, 'location_id'=>77, 'sequence'=>2],
            ['route_id'=>16, 'location_id'=>78, 'sequence'=>3],
            ['route_id'=>16, 'location_id'=>79, 'sequence'=>4],
            ['route_id'=>16, 'location_id'=>80, 'sequence'=>5],

            // Route 17: Sylhet to Dhaka
            ['route_id'=>17, 'location_id'=>81, 'sequence'=>1],
            ['route_id'=>17, 'location_id'=>82, 'sequence'=>2],
            ['route_id'=>17, 'location_id'=>83, 'sequence'=>3],
            ['route_id'=>17, 'location_id'=>84, 'sequence'=>4],
            ['route_id'=>17, 'location_id'=>85, 'sequence'=>5],

            // Route 18: Sylhet to Chittagong
            ['route_id'=>18, 'location_id'=>86, 'sequence'=>1],
            ['route_id'=>18, 'location_id'=>87, 'sequence'=>2],
            ['route_id'=>18, 'location_id'=>88, 'sequence'=>3],
            ['route_id'=>18, 'location_id'=>89, 'sequence'=>4],
            ['route_id'=>18, 'location_id'=>90, 'sequence'=>5],

            // Route 19: Sylhet to Coxs Bazar
            ['route_id'=>19, 'location_id'=>91, 'sequence'=>1],
            ['route_id'=>19, 'location_id'=>92, 'sequence'=>2],
            ['route_id'=>19, 'location_id'=>93, 'sequence'=>3],
            ['route_id'=>19, 'location_id'=>94, 'sequence'=>4],
            ['route_id'=>19, 'location_id'=>95, 'sequence'=>5],

            // Route 20: Sylhet to Rajshahi
            ['route_id'=>20, 'location_id'=>96, 'sequence'=>1],
            ['route_id'=>20, 'location_id'=>97, 'sequence'=>2],
            ['route_id'=>20, 'location_id'=>98, 'sequence'=>3],
            ['route_id'=>20, 'location_id'=>99, 'sequence'=>4],
            ['route_id'=>20, 'location_id'=>100, 'sequence'=>5],

            // Route 21: Sylhet to Khulna
            ['route_id'=>21, 'location_id'=>101, 'sequence'=>1],
            ['route_id'=>21, 'location_id'=>102, 'sequence'=>2],
            ['route_id'=>21, 'location_id'=>103, 'sequence'=>3],
            ['route_id'=>21, 'location_id'=>104, 'sequence'=>4],
            ['route_id'=>21, 'location_id'=>105, 'sequence'=>5],

            // Route 22: Khulna to Dhaka
            ['route_id'=>22, 'location_id'=>106, 'sequence'=>1],
            ['route_id'=>22, 'location_id'=>107, 'sequence'=>2],
            ['route_id'=>22, 'location_id'=>108, 'sequence'=>3],
            ['route_id'=>22, 'location_id'=>109, 'sequence'=>4],
            ['route_id'=>22, 'location_id'=>110, 'sequence'=>5],

            // Route 23: Khulna to Chittagong
            ['route_id'=>23, 'location_id'=>111, 'sequence'=>1],
            ['route_id'=>23, 'location_id'=>112, 'sequence'=>2],
            ['route_id'=>23, 'location_id'=>113, 'sequence'=>3],
            ['route_id'=>23, 'location_id'=>114, 'sequence'=>4],
            ['route_id'=>23, 'location_id'=>115, 'sequence'=>5],

            // Route 24: Khulna to Barisal
            ['route_id'=>24, 'location_id'=>116, 'sequence'=>1],
            ['route_id'=>24, 'location_id'=>117, 'sequence'=>2],
            ['route_id'=>24, 'location_id'=>118, 'sequence'=>3],
            ['route_id'=>24, 'location_id'=>119, 'sequence'=>4],
            ['route_id'=>24, 'location_id'=>120, 'sequence'=>5],

            // Route 25: Khulna to Jessore
            ['route_id'=>25, 'location_id'=>121, 'sequence'=>1],
            ['route_id'=>25, 'location_id'=>122, 'sequence'=>2],
            ['route_id'=>25, 'location_id'=>123, 'sequence'=>3],
            ['route_id'=>25, 'location_id'=>124, 'sequence'=>4],
            ['route_id'=>25, 'location_id'=>125, 'sequence'=>5],
        ];


        DB::table('location_route')->insert($location_routes);
    }
}
