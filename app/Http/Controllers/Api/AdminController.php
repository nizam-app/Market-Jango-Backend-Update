<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
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
            if($vendors->isEmpty()){
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
            if($vendors->isEmpty()){
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
                'user:id,name,image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
                ->paginate(10);
            if($vendors->isEmpty()){
                return ResponseHelper::Out('success', 'No suspended vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All suspended vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended vendor
    public function acceptOrRejectVendor(Request $request, $id): JsonResponse
    {
        try {
            $vendor = Vendor::with([
                'user:id,name,image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
            ->where('id', $id)
            ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
            ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found for this user ID', null, 404);
            }
            //get user status
            $user = $vendor->user;
            //update status
            $user->update(['status' => $request->input('status')]);
            return ResponseHelper::Out('success', 'Vendor status update successfully', $vendor, 200);
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

            if($vendors===0){
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

            if($vendors===0){
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

            if($drivers===0){
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

            if($drivers===0){
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
            ->select(['id','name','vendor_id', 'created_at', 'is_active'])
            ->paginate(10);
            if($products->isEmpty()){
                return ResponseHelper::Out('success', 'No pending product found', $products, 200);
            }
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $products, 200);
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
            ->select(['id','name','description','regular_price','sell_price','image', 'public_id','vendor_id', 'color', 'size', 'created_at', 'category_id', 'is_active'])
            ->first();
            if(!$products){
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
                'user:id,name'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Pending');
                })
                ->select('id', 'user_id','created_at','location', 'car_name','car_model')
                ->paginate(10);
            if($driver->isEmpty()){
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
                'user:id,name,image,phone,is_active'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->select('id', 'user_id','created_at','location', 'car_name','car_model','price')
                ->paginate(10);
            if($driver->isEmpty()){
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
                'user:id,name'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->select('id', 'user_id','created_at','location', 'car_name','car_model')
                ->paginate(10);
            if($driver->isEmpty()){
                return ResponseHelper::Out('success', 'No suspended driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All suspended driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
