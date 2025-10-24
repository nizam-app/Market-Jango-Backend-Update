<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductVariantController extends Controller
{
    // Get All Product Attributes
//    public function index(Request $request): JsonResponse
//    {
//        try {
//            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
//            if (!$vendor) {
//                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
//            }
//            $attributes = ProductAttribute::where('vendor_id',$vendor->id)->with(['attributeValues:id,name,product_attribute_id'])->select('id','name','vendor_id')->get();
//            return ResponseHelper::Out('success', 'All product attribute successfully fetched', $attributes, 200);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }
//
//   // Get All Product Attributes by vendor
    public function allAttributes(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $attributes = ProductAttribute::where('vendor_id',$vendor->id)->with(['attributeValues:id,name,product_attribute_id'])->select('id','name','vendor_id')->get();
            if($attributes->isEmpty()){
                return ResponseHelper::Out('success', 'You have no attribute value', $attributes, 200);
            }
            return ResponseHelper::Out('success', 'All product attribute successfully fetched', $attributes, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //single product attribute
    public function show(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $attributes = ProductAttribute::where('vendor_id',$vendor->id)->where('id', $request->input('id'))->with(['attributeValues:id,name,product_attribute_id'])->select('id','name','vendor_id')->get();
            if($attributes->isEmpty()){
                return ResponseHelper::Out('success', 'You have no attribute value', $attributes, 200);
            }
            return ResponseHelper::Out('success', 'All product attribute successfully fetched', $attributes, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Product attribute
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:20'
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $attribute = ProductAttribute::create([
                'name'       => $request->input('name'),
                'vendor_id' => $vendor->id,
            ]);
            return ResponseHelper::Out('success', 'Product attribute successfully created', $attribute, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Update Product attribute
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:20',
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find attribute check vendor
            $variant = ProductAttribute::where('id', $id)
               ->where('vendor_id', $vendor->id)
                ->first();
            if (!$variant) {
                return ResponseHelper::Out('failed', 'Product variant not found', null, 404);
            }
            $variant->update([
                'name' => $request->input('name'),
            ]);
            return ResponseHelper::Out('success', 'Product variant successfully updated', $variant, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Delete Product Variant
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->with('user')->first();

            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find attribute check vendor
            $variant = ProductAttribute::where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->first();
            if (!$variant) {
                return ResponseHelper::Out('failed', 'Product attribute not found or not owned by vendor', null, 404);
            }
            $variant->delete();
            return ResponseHelper::Out('success', 'Product attribute successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
