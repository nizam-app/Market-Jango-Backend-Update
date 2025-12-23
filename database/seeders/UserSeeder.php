<?php

namespace Database\Seeders;

use App\Helpers\ResponseHelper;
use App\Models\Buyer;
use App\Models\Driver;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Transport;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Create users (if not exist)P
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 🔹 Create role (if not exist)
        $role = Role::firstOrCreate(
            ['name' => 'Owner']
        );
        $permissions = [
            'category_create',
            'category_update',
            'category_delete',
            'category_assign_vendor',

            'admin_invoice_create',
            'admin_view',
            'admin_vendor_list',
            'admin_driver_list',
            'admin_buyer_list',
            'admin_transport_list',

            'admin_create_vendor',
            'admin_create_driver',
            'admin_create_admin',
            'admin_update_admin',

            'vendor_view_active',
            'vendor_view_pending',
            'vendor_view_suspended',
            'vendor_status_update',
            'vendor_count',
            'vendor_request_count',

            'driver_view',
            'driver_view_suspended',
            'driver_request_list',
            'driver_request_count',
            'driver_count',
            'driver_status_update',

            'buyer_status_update',
            'transport_status_update',

            'product_status_update',
            'product_request_list',
            'product_request_details',
            'product_admin_list',

            'order_view_all',
            'order_view_not_delivered',

            'admin_select_update',
            'admin_category_update',

            'user_delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        
        // 🔹 Assign all permissions to role
        $permissions = Permission::pluck('id')->all();
        // add permission  to role
        $role->permissions()->sync($permissions);
        $admin->roles()->sync([$role]);
        //admin assign to role
        $role->syncPermissions($permissions);

        // 🔹 Assign role to users
        $superAdmin->assignRole($role);
        $admin->roles()->sync([$role]);
//        $superAdmin = User::create([
//            'name' => 'Super Admin',
//            'email' => 'superadmin@gmail.com',
//            'password' => Hash::make('password')
//        ]);
//
//        $admin = User::create([
//            'name' => 'Admin',
//            'email' => 'admin@gmail.com',
//            'password' => Hash::make('password')
//        ]);
//
//        //create role
//        $role = Role::create(['name'=>'Admin']);
//
//        //Assign Permissions to Role
//        $permission = Permission::pluck('id')->all();
//        $role->syncPermissions($permission);
//
//        //Assign Role to User
//        $superAdmin->assignRole($role);
//        $admin->syncRoles($role);
//        $faker = Faker::create();
//        $userTypes = ['buyer', 'vendor', 'driver', 'transport'];
//        foreach ($userTypes as $type) {
//            for ($i = 1; $i <= 5; $i++) {
//                // Status set according to user type
//                $status = in_array($type, ['buyer', 'transport']) ? 'Approved' : 'Pending';
//
//                $id = DB::table('users')->insertGetId([
//                    'user_type' => $type,
//                    'name' => $faker->name,
//                    'email' => $faker->unique()->safeEmail,
//                    'phone' => $faker->unique()->numerify('01#########'),
//                    'otp' => null,
//                    'phone_verified_at' => now(),
//                    'password' => Hash::make('password'),
//                    'language' => $faker->randomElement(['English','Français','Русский','Tiếng Việt']),
//                    'image' => $faker->imageUrl(200, 200, 'people'),
//                    'status' => 'Approved',
//                    'expires_at' => null,
//                    'remember_token' => null,
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//                if ($type === 'buyer') {
//                    Buyer::create(['user_id' => $id]);
//                } elseif ($type === 'transport') {
//                    Transport::create(['user_id' => $id]);
//                } elseif ($type === 'vendor') {
//                    Vendor::create([
//                        'country' => 'Bangladesh',
//                        'business_name' => $faker->name,
//                        'business_type' => 'Restaurant',
//                        'address' => substr($faker->address, 0, 50),
//                        'user_id' => $id,
//                    ]);
//                } elseif ($type === 'driver') {
//                    Driver::create([
//                        'car_name' => $faker->randomElement(['Toyota', 'Honda', 'Nissan', 'Hyundai', 'Ford', 'BMW', 'Mercedes']),
//                        'car_model' => $faker->word,
//                        'location' => $faker->unique()->city,
//                        'price' => $faker->numberBetween(100, 10000),
//                        'user_id' => $id,
//                        'route_id' => $faker->numberBetween(1, 20),
//                    ]);
//                }
//            }
//        }
    }
}
