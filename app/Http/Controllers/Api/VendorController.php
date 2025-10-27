<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{

    //face all vendor
    public function index():JsonResponse
    {
        try {
            $driver = Vendor::all();
            return response()->json([
                'status' => 'success',
                'data' => $driver
            ],200);

        }catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    //vendor categories
    public function category(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id',  $request->header('id'))->select(['id', 'user_id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $categories = Category::where('vendor_id', $vendor->id)
                ->with([
                    'products:id,name,description,sell_price,regular_price,category_id',
                    'products.images:id,product_id,image_path,public_id',
                    'categoryImages:id,category_id,image_path',
                    'vendor:id,country,address,business_name,business_type,user_id',
                    'vendor.user:id,name,image,email,phone,language',

                ])
                ->select(['id', 'name', 'status','vendor_id'])
                ->paginate(10);
            return ResponseHelper::Out('success', 'All categories successfully fetched', $categories, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function categoryByProduct(Request $request, $id): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id',  $request->header('id'))->select(['id', 'user_id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $categories = Product::where('vendor_id', $vendor->id)
                ->with([
                    'products:id,name,description,sell_price,regular_price,category_id',
                    'products.images:id,product_id,image_path,public_id',
                    'categoryImages:id,category_id,image_path',
                    'vendor:id,country,address,business_name,business_type,user_id',
                    'vendor.user:id,name,image,email,phone,language',

                ])
                ->select(['id', 'name', 'status','vendor_id'])
                ->paginate(10);
            return ResponseHelper::Out('success', 'All categories successfully fetched', $categories, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //vendor attributes
    public function attribute(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id',  $request->header('id'))->select(['id', 'user_id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $attributes = ProductAttribute::where('vendor_id',$vendor)->with('attributeValues')->get();
            return ResponseHelper::Out('success', 'All product attribute successfully fetched', $attributes, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //search by vendor name
    public function searchByName(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);
            $name = $request->input('name');
            $users = User::where('user_type', '=', 'vendor')->get();
//            $vendors = Vendor::where('name', 'LIKE', "%{$name}%")->get();
            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No vendors found.'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'data' => $users
            ],200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    //category wise product
    public function vendorCategoryWiseProduct(Request $request, $id): JsonResponse
    {
        try {
            // First: Search by name
            $products = Product::where('category_id', $id)
                ->with([
                    'category:id,name,description',
                    'images:id,image_path,product_id'
                ])
                ->select(['id','name','description','regular_price','sell_price','image','vendor_id','category_id', 'color', 'size'])
                ->paginate(10);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no products', ['products'=>$products ], 200);
            }
            return ResponseHelper::Out('success', 'Products found', ['products'=> $products], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
