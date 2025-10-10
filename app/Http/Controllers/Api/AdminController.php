<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
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
                'user:id,name,user_image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
                ->paginate(10);

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
                'user:id,name,user_image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
            ->whereHas('user', function ($query) {
                $query->where('status', 'Pending');
            })
            ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
            ->paginate(10);
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended vendor
    public function suspendedVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user:id,name,user_image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
                ->paginate(10);
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended vendor
    public function acceptOrRejectVendor(Request $request, $id): JsonResponse
    {
        try {
            $vendor = Vendor::with([
                'user:id,name,user_image,email,phone,language,status,phone_verified_at',
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
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendor, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
