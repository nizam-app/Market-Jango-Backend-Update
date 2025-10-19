<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Dotenv\Validator;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorHomePageController extends Controller
{
//screen 1
    //vendor details
    public function show(Request $request): JsonResponse
    {
        try {
            $vendor = User::where('id', $request->header('id'))->select(['id', 'name', 'image','public_id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            return ResponseHelper::Out('success', 'All categories successfully fetched', $vendor, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //update vendor image
    public function update(Request $request): JsonResponse
    {
        try {
            $vendor = User::where('id', $request->header('id'))->select(['id', 'name', 'image','public_id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $uploadedFile = null;
            if ($request->hasFile('image')) {
                $request->validate([
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);
                // Delete old image if exists
                if (!empty($vendor->public_id)) {
                    FileHelper::delete($vendor->public_id);
                }
                //Upload new image
                $file = $request->file('image');
                $uploadedFile = FileHelper::upload($file, $vendor->user_type);
                $vendor->image = $uploadedFile[0]['url'];
                $vendor->public_id =  $uploadedFile[0]['public_id'];
                $vendor->save();
            }
            return ResponseHelper::Out('success', 'update image successfully', $vendor, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //search vendor product
    public function productSearchByVendor(Request $request): JsonResponse
    {
        try {
            // Vendor fetch
            $vendor = Vendor::where('user_id', $request->header('id'))
                ->select(['id', 'user_id'])
                ->first();

            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            //Validate search query
            $request->validate([
                'query' => 'required|string'
            ]);
            $query = $request->input('query');
            // First: Search by name
            $products = Product::where('vendor_id', $vendor->id)
                ->where('name', 'LIKE', "%{$query}%")
                ->with(['category:id,name,description','images:id,image_path,product_id'])
                ->select(['id','name','description','previous_price','current_price','image','vendor_id','category_id'])
                ->orderByRaw("(LENGTH(name) - LENGTH(REPLACE(name, ?, ''))) DESC", [$query])
                ->paginate(10);

            // If no products found by name, search by description
            if ($products->isEmpty()) {
                $products = Product::where('vendor_id', $vendor->id)
                    ->where('description', 'LIKE', "%{$query}%")
                    ->with(['category:id,name,description','images:id,image_path,product_id'])
                    ->select(['id','name','description','previous_price','current_price','image','vendor_id','category_id'])
                    ->orderByRaw("(LENGTH(description) - LENGTH(REPLACE(description, ?, ''))) DESC", [$query])
                    ->paginate(10);
            }
            //Check if any product found
            if ($products->isEmpty()) {
                return ResponseHelper::Out('failed', 'No products found', null, 404);
            }
            // Return products
            return ResponseHelper::Out('success', 'Products found', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //product by vendor
    public function vendorProduct(Request $request): JsonResponse
    {
        try {
            // Vendor fetch
            $vendor = Vendor::where('user_id', $request->header('id'))
                ->select(['id', 'user_id'])
                ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            //Validate search query
            $request->validate([
                'query' => 'required|string'
            ]);
            // First: Search by name
            $products = Product::where('vendor_id', $vendor->id)
                ->with(['category:id,name,description','images:id,image_path,product_id'])
                ->select(['id','name','description','previous_price','current_price','image','vendor_id','category_id'])
                ->paginate(10);
            return ResponseHelper::Out('success', 'Products found', ['all'=> $products->count(), 'products'=>$products], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
