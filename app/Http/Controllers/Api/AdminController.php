<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CalculateDistance;
use App\Helpers\FileHelper;
use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\AdminInviteMail;
use App\Mail\DriverInviteMail;
use App\Mail\VendorInviteMail;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceStatusLog;
use App\Models\Product;
use App\Models\Role;
use App\Models\Transport;
use App\Models\User;
use App\Models\UserImage;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // Get All approved vendor
    public function index(): JsonResponse
    {
        try {
            $vendors = User::where('user_type', 'admin')
                ->with(['admin'])
                ->paginate(10);
            if ($vendors->isEmpty()) {
                return ResponseHelper::Out('success', 'No approved vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //  NEW ADMIN CREATE
    public function createAdmin(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role'  => 'required|string',
            ]);
            $requestRole = $request->role;
            $roleId = Role::where('name', $requestRole)->first();
            // get login buyers
            $owner = Admin::where('user_id', $request->header('id'))->where('role', 'Owner')->first();
            if (!$owner) {
                return ResponseHelper::Out('failed', 'Your are not owner', null, 404);
            }
            //invite token generate
            $tempPassword = Str::random(10);
            // user create
            $user = User::create([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'user_type'             => 'admin',
                'status'             => 'Approved',
                'password'             =>  Hash::make($tempPassword),
                'must_change_password' => true,
            ]);
            $admin = Admin::create([
                'user_id'           => $user->id,
                'role'              => $requestRole
            ]);
            $user->roles()->sync([$roleId]);
            Mail::to($user->email)->send(new AdminInviteMail($user, $tempPassword));
            $data = [
                'user' => $user,
                'admin' => $admin
            ];
            return ResponseHelper::Out('success', 'Admin user created and invite sent.', $data, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    //UPDATE INFO VENDOR, DRIVER, TRANSPORT, BUYER BY ADMIN
    public function adminUpdateUserInfo(Request $request, $user_id): JsonResponse
    {
        DB::beginTransaction();
        try {
                $validated = $request->validate([
                'email' => 'nullable|email'
            ]);
            $user = User::with(['buyer', 'driver', 'vendor', 'transport'])->find($user_id);
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            $userType = $user->user_type;

            // Update common user fields
            $userData = [
                'name' => $request->input('name', $user->name),
                'email' => $request->input('email', $user->email),
                'status' => $request->input('status', $user->status),
                'phone' => $request->input('phone', $user->phone),
                'language' => $request->input('language', $user->language),
            ];

            // Only hash password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->input('password'));
            }

            // Handle main user image upload
            if ($request->hasFile('image')) {
                $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);
                if (!empty($user->public_id)) {
                    FileHelper::delete($user->public_id);
                }
                $file = $request->file('image');
                $uploadedFile = FileHelper::upload($file, $userType);
                $userData['image'] = $uploadedFile[0]['url'];
                $userData['public_id'] = $uploadedFile[0]['public_id'];
            }

            $user->update($userData);

            // Role-specific updates
            switch ($userType) {
                case 'buyer':
                    $buyer = $user->buyer;
                    if (!$buyer) throw new Exception('Buyer profile not found');
                    $buyer->update([
                        "gender" => $request->input('gender', $buyer->gender),
                        "age" => $request->input('age', $buyer->age),
                        "address" => $request->input('address', $buyer->address),
                        "state" => $request->input('state', $buyer->state),
                        "postcode" => $request->input('postcode', $buyer->postcode),
                        "country" => $request->input('country', $buyer->country),
                        "ship_name" => $request->input('ship_name', $buyer->ship_name),
                        "ship_email" => $request->input('ship_email', $buyer->ship_email),
                        "ship_location" => $request->input('ship_location', $buyer->ship_location),
                        "ship_latitude" => $request->input('ship_latitude', $buyer->ship_latitude),
                        "ship_longitude" => $request->input('ship_longitude', $buyer->ship_longitude),
                        "ship_country" => $request->input('ship_country', $buyer->ship_country),
                        "ship_phone" => $request->input('ship_phone', $buyer->ship_phone),
                        "description" => $request->input('description', $buyer->description),
                        "location" => $request->input('location', $buyer->location),
                    ]);
                    break;
                case 'vendor':
                    $vendor = $user->vendor;
                    if (!$vendor) throw new Exception('Vendor profile not found');

                    // Vendor cover image
                    if ($request->hasFile('cover_image')) {
                        $request->validate(['cover_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);
                        if (!empty($vendor->public_id)) FileHelper::delete($vendor->public_id);
                        $file = $request->file('cover_image');
                        $uploadedFile = FileHelper::upload($file, $userType.'/cover_image');
                        $vendor->cover_image = $uploadedFile[0]['url'];
                        $vendor->public_id = $uploadedFile[0]['public_id'];
                        $vendor->save();
                    }

                    // Vendor multiple files
                    if ($request->hasFile('files')) {
                        $oldImages = UserImage::where('user_type', 'vendor')->where('user_id', $vendor->id)->get();
                        foreach ($oldImages as $old) {
                            if (!empty($old->public_id)) FileHelper::delete($old->public_id);
                            $old->delete();
                        }
                        $files = $request->file('files');
                        $uploadedFiles = FileHelper::upload($files, $userType);
                        foreach ($uploadedFiles as $file) {
                            UserImage::create([
                                'image_path' => $file['url'],
                                'public_id' => $file['public_id'],
                                'user_id' => $vendor->id,
                                'user_type' => $userType,
                                'file_type' => 'image'
                            ]);
                        }
                    }

                    $vendor->update([
                        "country" => $request->input('country', $vendor->country),
                        "address" => $request->input('address', $vendor->address),
                        "open_time" => $request->input('open_time', $vendor->open_time),
                        "close_time" => $request->input('close_time', $vendor->close_time),
                        "business_name" => $request->input('business_name', $vendor->business_name),
                        "longitude" => $request->input('longitude', $vendor->longitude),
                        "latitude" => $request->input('latitude', $vendor->latitude),
                        "business_type" => $request->input('business_type', $vendor->business_type),
                    ]);
                    break;

                case 'driver':
                    $driver = $user->driver;
                    if (!$driver) throw new Exception('Driver profile not found');

                    $driver->update([
                        "car_name" => $request->input('car_name', $driver->car_name),
                        "car_model" => $request->input('car_model', $driver->car_model),
                        "location" => $request->input('location', $driver->location),
                        "price" => $request->input('price', $driver->price),
                    ]);

                    if ($request->has('route_ids')) {
                        $driver->routes()->syncWithoutDetaching($request->route_ids);
                    }

                    if ($request->hasFile('files')) {
                        $oldImages = UserImage::where('user_type', 'driver')->where('user_id', $driver->id)->get();
                        foreach ($oldImages as $old) {
                            if (!empty($old->public_id)) FileHelper::delete($old->public_id);
                            $old->delete();
                        }
                        $files = $request->file('files');
                        $uploadedFiles = FileHelper::upload($files, $userType);
                        foreach ($uploadedFiles as $file) {
                            UserImage::create([
                                'image_path' => $file['url'],
                                'public_id' => $file['public_id'],
                                'user_id' => $driver->id,
                                'user_type' => $userType,
                                'file_type' => 'image'
                            ]);
                        }
                    }
                    break;

                case 'transport':
                    $transport = $user->transport;
                    if (!$transport) throw new Exception('Transport profile not found');

                    $transport->update([
                        "address" => $request->input('address', $transport->address),
                        "longitude" => $request->input('longitude', $transport->longitude),
                        "latitude" => $request->input('latitude', $transport->latitude),
                    ]);
                    break;

                default:
                    throw new Exception('Invalid user type');
            }
            DB::commit();
            return ResponseHelper::Out('success', 'User updated successfully', $user, 200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //  NEW VENDOR CREATE
    public function createVendor(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email'
            ]);
            //invite token generate
            $tempPassword = $request->input('password');
            // user create
            $user = User::create([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'user_type'             => 'vendor',
                'status'             => $request->input('status') ?? 'Approved',
                'password'             =>  Hash::make($tempPassword),
                'must_change_password' => true,
            ]);
            $vendor = Vendor::Create([
                'user_id'      => $user->id,
                'country'        => $request->input('country'),
                'business_name'  => $request->input('business_name'),
                'business_type'  => $request->input('business_type'),
                'address'        => $request->input('address'),
                'longitude'        => $request->input('longitude'),
                'latitude'        => $request->input('latitude'),
            ]);
            if ($request->hasFile('files')) {
                $oldImages = UserImage::where('user_type', 'vendor')->where('user_id', $vendor->id)->get();
                if ($oldImages->count() > 0) {
                    foreach ($oldImages as $old) {
                        if (!empty($old->public_id)) {
                            FileHelper::delete($old->public_id);
                        }
                        $old->delete();
                    }
                }
                $files = $request->file('files');
                $uploadedFiles = FileHelper::upload($files, $user->user_type);
                foreach ($uploadedFiles as $file) {
                    UserImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'user_id'    => $vendor->id,
                        'user_type'  => $user->user_type,
                        'file_type'  => 'image'
                    ]);
                }
            }
            //mail send
            Mail::to($user->email)->send(new VendorInviteMail($user, $tempPassword));
            $data = [
                'user' => $user,
                'vendor' => $vendor
            ];
            DB::commit();
            return ResponseHelper::Out('success', 'Admin vendor created', $data, 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //  NEW DRIVER CREATE
    public function createDriver(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'route_ids'   => 'nullable|array',
                'route_ids.*' => 'exists:routes,id',
            ]);
            //invite token generate
            $tempPassword = $request->input('password');
            // user create
            $user = User::create([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'user_type'             => 'driver',
                'status'             => $request->input('status') ?? 'Approved',
                'phone' => $request->input('phone'),
                'password'             =>  Hash::make($tempPassword),
                'must_change_password' => true,
            ]);
            $driver = Driver::Create([
                'car_name' => $request->input('car_name'),               
                'car_model' => $request->input('car_model'),
                'location' => $request->input('location'),
                'price' => $request->input('price'),
                'user_id' => $user->id
            ]);
            if ($request->has('route_ids')) {
                $driver->routes()->syncWithoutDetaching($request->route_ids);
            }
            if ($request->hasFile('files')) {
                $oldImages = UserImage::where('user_type', 'driver')->where('user_id', $driver->id)->get();
                if ($oldImages->count() > 0) {
                    foreach ($oldImages as $old) {
                        if (!empty($old->public_id)) {
                            FileHelper::delete($old->public_id);
                        }
                        $old->delete();
                    }
                }
                $files = $request->file('files');
                $uploadedFiles = FileHelper::upload($files, $user->user_type);
                foreach ($uploadedFiles as $file) {
                    UserImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'user_id'    => $driver->id,
                        'user_type'  => $user->user_type,
                        'file_type'  => 'image'
                    ]);
                }
            }
            //mail send
            Mail::to($user->email)->send(new DriverInviteMail($user, $tempPassword));

            $driver->load('routes');

            $data = [
                'user'   => $user,
                'driver' => $driver
            ];
            DB::commit();
            return ResponseHelper::Out('success', 'Admin user created and invite sent.', $data, 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //UPDATE ADMIN
    public function updateAdmin(Request $request, $id)
    {
        try {
            // validate input
            $validated = $request->validate([
                'name'  => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $id,
                'role'  => 'nullable|string',
                'status'  => 'nullable|string',
            ]);
            // check logged-in owner
            $owner = Admin::where('user_id', $request->header('id'))
                ->where('role', 'Owner')
                ->first();
            if (!$owner) {
                return ResponseHelper::Out('failed', 'You are not owner', null, 403);
            }
            // find user
            $user = User::where('id', $id)->where('user_type', 'admin')->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Admin user not found', null, 404);
            }
            // get admin profile
            $admin = Admin::where('user_id', $id)->first();
            if (!$admin) {
                return ResponseHelper::Out('failed', 'Admin profile not found', null, 404);
            }
            // update user basic info
            $user->update([
                'name'  => $validated['name']  ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
            ]);
            // update admin role
            if (!empty($request->role)) {

                // check role exists in spatie roles table
                $roleId = Role::where('name', $request->role)->first();
                if (!$roleId) {
                    return ResponseHelper::Out('failed', 'Invalid role', null, 400);
                }
                // update admin table role
                $admin->update([
                    'role' => $request->role
                ]);
                // update spatie roles
                $user->roles()->sync([$roleId->id]);
            }
            $admin->update([
                'status' =>  $validated['status'] ?? $admin->status
            ]);
            $data = [
                'user'  => $user,
                'admin' => $admin
            ];
            return ResponseHelper::Out('success', 'Admin updated successfully', $data, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //UPDATE  
    // Get All approved vendor
    public function activeVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user:id,name,image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
                ->paginate(10);
            if ($vendors->isEmpty()) {
                return ResponseHelper::Out('success', 'No approved vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // pending vendor
    public function pendingVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user:id,name,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Pending');
                })
                ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
                ->paginate(10);
            if ($vendors->isEmpty()) {
                return ResponseHelper::Out('success', 'No pending vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All pending vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended vendor
    public function suspendedVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user',
                'images',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->paginate(10);
            if ($vendors->isEmpty()) {
                return ResponseHelper::Out('success', 'No suspended vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All suspended vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // admin vendor
    public function adminVendor(Request $request): JsonResponse
    {
        try {
            $status = $request->input('status');

            $query = User::where('user_type', 'vendor')
                ->with([
                    'vendor',
                    'vendor.products',
                    'vendor.products.images',
                    'vendor.images'
                ]);

            if (!empty($status)) {
                $query->where('status', $status);
            }
            $vendors = $query->paginate(10);
            $vendors->getCollection()->transform(function ($vendor) {
                $vendor->product_count =
                    $vendor->vendor ? $vendor->vendor->products->count() : 0;
                return $vendor;
            });
            return ResponseHelper::Out('success', 'Vendors fetched successfully', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // admin driver
    public function adminDriver(Request $request): JsonResponse
    {
        try {
            $status = $request->input('status');

            $query = User::where('user_type', 'driver')
                ->with([
                    'driver',
                    'driver.user',
                    'driver.images',
                    'driver.routes'
                ]);

            if (!empty($status)) {
                $query->where('status', $status);
            }
            $drivers = $query->paginate(10);
            $drivers->getCollection()->transform(function ($driver) {
                $driver->route_count =
                    $driver->driver && $driver->driver->routes
                    ? $driver->driver->routes->count()
                    : 0;

                return $driver;
            });

            return ResponseHelper::Out('success', 'Vendors fetched successfully', $drivers, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //vendor status update
    public function vendorStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $vendor = Vendor::with([
                'user',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->where('id', $id)
                ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found for this user ID', null, 404);
            }
            //get user status
            $user = $vendor->user;
            //update status
            $user->update([
                'status' => $request->input('status') ?? $user->status,
                'note' => $request->input('note') ?? $user->note,
            ]);
            return ResponseHelper::Out('success', 'Vendor status update successfully', $vendor, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Driver status update
    public function driverStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->where('id', $id)
                ->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found for this user ID', null, 404);
            }
            //get user status
            $user = $driver->user;
            //update status
            $user->update([
                'status' => $request->input('status') ?? $user->status,
                'note' => $request->input('note') ?? $user->note,
            ]);
            return ResponseHelper::Out('success', 'Driver status update successfully', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //BUYER STATUS UPDATE
    public function buyerStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $buyer = Buyer::with(['user'])
                ->where('id', $id)
                ->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found for this user ID', null, 404);
            }
            //get user status
            $user = $buyer->user;
            //update status
            $user->update(['status' => $request->input('status') ?? $user->status]);
            return ResponseHelper::Out('success', 'Buyer status update successfully', $buyer, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //TRANSPORT STATUS UPDATE
    public function transportStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $transport = Transport::with(['user'])
                ->where('id', $id)
                ->first();
            if (!$transport) {
                return ResponseHelper::Out('failed', 'Transport not found for this user ID', null, 404);
            }
            //get user status
            $user = $transport->user;
            //update status
            $user->update(['status' => $request->input('status') ?? $user->status]);
            return ResponseHelper::Out('success', 'Transport status update successfully', $transport, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //GET ALL BUYER
    public function buyers(Request $request): JsonResponse
    {
        try {
            $buyer = Buyer::with('user')->paginate('10');
            if ($buyer->isEmpty()) {
                return ResponseHelper::Out('success', 'Buyer not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Buyer successfully fetched', $buyer, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //GET ALL TRANSPORT
    public function transports(Request $request): JsonResponse
    {
        try {
            $transport = Transport::with('user')->paginate('10');
            if ($transport->isEmpty()) {
                return ResponseHelper::Out('success', 'Transport not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Transport successfully fetched', $transport, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //total vendor count
    public function vendorCount(): JsonResponse
    {
        try {
            $vendors = Vendor::whereHas('user', function ($query) {
                $query->where('status', 'Approved');
            })
                ->count();

            if ($vendors === 0) {
                return ResponseHelper::Out('success', 'No approved vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //request vendor count
    public function vendorRequestCount(): JsonResponse
    {
        try {
            $vendors = Vendor::whereHas('user', function ($query) {
                $query->where('status', 'Pending');
            })
                ->count();

            if ($vendors === 0) {
                return ResponseHelper::Out('success', 'No pending vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //total vendor count
    public function driverCount(): JsonResponse
    {
        try {
            $drivers = Driver::whereHas('user', function ($query) {
                $query->where('status', 'Approved');
            })
                ->count();

            if ($drivers === 0) {
                return ResponseHelper::Out('success', 'No approved driver found', $drivers, 200);
            }
            return ResponseHelper::Out('success', 'All approved driver successfully fetched', $drivers, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //request vendor count
    public function driverRequestCount(): JsonResponse
    {
        try {
            $drivers = Driver::whereHas('user', function ($query) {
                $query->where('status', 'Pending');
            })
                ->count();

            if ($drivers === 0) {
                return ResponseHelper::Out('success', 'No pending driver found', $drivers, 200);
            }
            return ResponseHelper::Out('success', 'All approved driver successfully fetched', $drivers, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request product
    public function requestProduct(): JsonResponse
    {
        try {
            $products = Product::where('is_active', 0)
                ->with([
                    'category:id,name',
                    'vendor:id,user_id,address',
                    'vendor.user:id,name',
                ])
                ->select(['id', 'name', 'vendor_id', 'created_at', 'is_active'])
                ->paginate(10);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'No pending product found', $products, 200);
            }
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Product Status Update
    public function productStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'is_active' => 'required|in:0,1,2',
            ]);
            $product = Product::select(['id', 'is_active'])->find($id);
            if (!$product) {
                return ResponseHelper::Out('success', 'No pending product found', $product, 200);
            }
            $product->update([
                'is_active' => $request->input('is_active')
            ]);
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $product, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request product details
    public function requestProductDetails(Request $request, $id): JsonResponse
    {
        try {
            $products = Product::where('id', $id)
                ->with([
                    'category:id,name',
                    'vendor:id,user_id,created_at',
                    'vendor.user:id,name,image,public_id',
                ])
                ->select(['id', 'name', 'description', 'regular_price', 'sell_price', 'image', 'public_id', 'vendor_id', 'color', 'size', 'created_at', 'category_id', 'is_active'])
                ->first();
            if (!$products) {
                return ResponseHelper::Out('success', 'No pending product found', $products, 200);
            }
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request driver
    public function requestDriver(): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images',
                'route'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Pending');
                })
                ->select('id', 'user_id', 'created_at', 'location', 'car_name', 'car_model', 'price', 'rating', 'route_id')
                ->paginate(10);
            if ($driver->isEmpty()) {
                return ResponseHelper::Out('success', 'No pending driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All pending driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request driver details
    public function requestDriverDetails(Request $request): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user:id,name,image',
                'images',
                'route'
            ])
                ->where('id', $request->id)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Pending');
                })
                ->select('id', 'user_id', 'created_at', 'location', 'car_name', 'car_model', 'price', 'rating', 'route_id')
                ->paginate(10);
            if ($driver->isEmpty()) {
                return ResponseHelper::Out('success', 'No pending driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All pending driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // approved driver
    public function approvedDriver(): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->paginate(10);
            if ($driver->isEmpty()) {
                return ResponseHelper::Out('success', 'No active driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All active driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended driver
    public function suspendedDriver(): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->select('id', 'user_id', 'created_at', 'location', 'car_name', 'car_model')
                ->paginate(10);
            if ($driver->isEmpty()) {
                return ResponseHelper::Out('success', 'No suspended driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All suspended driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended driver
    public function suspendedDriverDetails(Request $request): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images:id,image_path,public_id,user_type,file_type,user_id',
                'route'
            ])
                ->where('id', $request->id)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->paginate(10);
            if ($driver->isEmpty()) {
                return ResponseHelper::Out('success', 'No suspended driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All suspended driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Get Single User
    public function driverDetails(Request $request): JsonResponse
    {
        try {
            $user = Driver::where('id', $request->input('id'))
                ->with(['user', 'images'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            return ResponseHelper::Out('success', 'Driver data fetched successfully', $user, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Driver Cancel Order
    public function notDeliveredOrder(Request $request): JsonResponse
    {
        try {
            $order = InvoiceItem::where('status', 'Not Deliver')
                ->with(['driver'])
                ->paginate(10);
            if (!$order) {
                return ResponseHelper::Out('success', 'not delivered order  not found', null, 404);
            }
            return ResponseHelper::Out('success', 'not delivered order data fetched successfully', $order, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //delete user
    public function destroy($id)
    {
        try {
            $route = User::where('id', $id)->with(['vendor', 'buyer', 'driver', 'transport'])->first();
            if (!$route) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $route->delete();
            return ResponseHelper::Out('success', 'User Delete successfully', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Get All order
    public function allOrder(Request $request): JsonResponse
    {
        try {

            // get cart data by login buyer
            $invoices = InvoiceItem::with(['invoice'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    function adminInvoice(Request $request, $driver_id, $order_item_id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $user = User::where('id', '=', $user_id)->with('admin')->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $driver = Driver::where('id', $driver_id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }

            $orderItem = InvoiceItem::where('id', $order_item_id)->with('invoice')->first();
            if (!$orderItem) {
                return ResponseHelper::Out('failed', 'Order item not found', null, 404);
            }
            $tax_ref = uniqid();
            $payment_status = 'Pending';
            $currency = "USD";
            $cus_name = $user->name;
            $cus_phone = $user->phone;
            $total = $driver->price;
            $vat = 0;
            $drop_lat = $orderItem->ship_latitude;
            $drop_long = $orderItem->ship_longitude;
            $pickup_lat = $orderItem->current_latitude;
            $pickup_long = $orderItem->current_longitude;
            $distance = CalculateDistance::Distance($pickup_lat, $pickup_long, $drop_lat, $drop_long);
            $subtotal = $total * $distance;
            $payable = $subtotal + $vat;
            $invoice = Invoice::create([
                'cus_name' => $cus_name,
                'cus_email' => $user_email,
                'cus_phone' => $cus_phone,
                'total' => $total,
                'vat' => $vat,
                'payable' => $payable,
                'tax_ref' => $tax_ref,
                'currency' => $currency,
                'payment_method' => "FW",
                'status' => $payment_status,
                'user_id' => $user_id
            ]);
            $invoiceStatusLogs = InvoiceStatusLog::create([
                'driver_id' => $driver_id,
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $orderItem->id
            ]);
            $paymentMethod = PaymentSystem::InitiatePayment($invoice);
            DB::commit();
            return ResponseHelper::Out('success', '', array(['paymentMethod' => $paymentMethod, 'payable' => $payable, 'vat' => $vat, 'total' => $payable]), 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('fail', 'Something went wrong', $e->getMessage(), 200);
        }
    }
    //GET TOP CATEGORY
    public function getTopCategory(Request $request): JsonResponse
    {
        try {
            //Get All Category But New Product First
            $products = Category::where('status', 'Active')->where('is_top_category', 1)->with([
                'products',
                'vendors.user:id,name',
                'vendors.reviews:id,vendor_id,review,rating',
                'categoryImages:id,image_path,public_id,category_id'
            ])
                ->latest()
                ->paginate(20);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no top products', [], 200);
            }
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
