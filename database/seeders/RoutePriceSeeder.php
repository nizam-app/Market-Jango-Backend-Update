<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoutePriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = [2, 3, 4, 5, 56, 57];

        $locations = array_merge(
            [6, 8],
            range(10, 18),
            range(21, 23),
            [26, 28, 29]
        );

        $data = [];

        for ($i = 0; $i < 1000; $i++) {

            $start = $locations[array_rand($locations)];
            $end   = $locations[array_rand($locations)];

            // start & end same হলে skip
            if ($start === $end) {
                $i--;
                continue;
            }

            $data[] = [
                'route_id'        => $routes[array_rand($routes)],
                'start_point_id'  => $start,
                'end_point_id'    => $end,
                'price'           => rand(1, 60),
                'radius_km'           => rand(1, 5),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        // Chunk করে insert (safe for large data)
        foreach (array_chunk($data, 500) as $chunk) {
            DB::table('location_routes')->insert($chunk);
        }
    }
}
