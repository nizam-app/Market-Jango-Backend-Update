<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Get All approved vendor
    public function activeVendor(): JsonResponse
    {
        try {
            $products = User::where('user_type','vendor')
                ->where('status', 'Approved')
                ->with(['vendor'=> function ($query) {
                    $query->select('id','user_id','country','address','business_name','business_type')
                        ->with([
                            'images:id,user_id,user_type,image_path,file_type',
                        ]);
                }])
                ->select('id','name','user_image','email','phone','language','status','phone_verified_at')->paginate(10);
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // pending vendor
    public function pendingVendor(): JsonResponse
    {
        try {
            $products = User::where('user_type','vendor')
                ->where('status', 'Pending')
                ->with(['vendor'=> function ($query) {
                    $query->select('id','user_id','country','address','business_name','business_type')
                        ->with([
                        'images:id,user_id,user_type,image_path,file_type',
                    ]);
                }])
                ->select('id','name','user_image','email','phone','language','status','phone_verified_at')->paginate(10);
            return ResponseHelper::Out('success', 'All Pending vendor successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended vendor
    public function suspendedVendor(): JsonResponse
    {
        try {
            $products = User::where('user_type','vendor')
                ->where('status', 'Rejected')
                ->with(['vendor'=> function ($query) {
                    $query->select('id','user_id','country','address','business_name','business_type')
                        ->with([
                            'images:id,user_id,user_type,image_path,file_type',
                        ]);
                }])
                ->select('id','name','user_image','email','phone','language','status','phone_verified_at')->paginate(10);
            return ResponseHelper::Out('success', 'All suspended vendor successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
