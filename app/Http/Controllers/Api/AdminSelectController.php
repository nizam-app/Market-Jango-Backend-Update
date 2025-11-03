<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminSelect;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminSelectController extends Controller
{
    //get all admin selected
    public function index(Request $request): JsonResponse
    {
        try {
            $selects = AdminSelect::with([
                'product:id,name,regular_price,sell_price,image,category_id,vendor_id',
                'product.images:id,product_id,image_path,public_id',
                'product.vendor:id,user_id',
                'product.vendor.user:id,name',
                'product.vendor.reviews:id,vendor_id,description,rating',
                'product.category:id,name',
            ])
                ->select('id','key','product_id')
                ->latest()
                ->paginate(10);

            if ($selects->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found under this key', $selects, 200);
            }

            return ResponseHelper::Out('success', 'Products fetched successfully by key', $selects, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //get top product
    public function getTopProduct(Request $request): JsonResponse
    {
        try {
            $selects = AdminSelect::where('key', 'top_product')
              ->with([
                  'product:id,name,regular_price,sell_price,image,category_id,vendor_id',
                  'product.images:id,product_id,image_path,public_id',
                  'product.vendor:id,user_id',
                  'product.vendor.user:id,name',
                  'product.vendor.reviews:id,vendor_id,description,rating',
                  'product.category:id,name',
              ])
                  ->select('id','key','product_id')
                ->latest()
                  ->paginate(10);

            if ($selects->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found under this key', $selects, 200);
            }

            return ResponseHelper::Out('success', 'Products fetched successfully by key', $selects, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Get new items
    public function getNewItem(Request $request): JsonResponse
    {
        try {
            $selects = AdminSelect::where('key', 'new_item')
                ->with([
                    'product:id,name,regular_price,sell_price,image,category_id,vendor_id',
                    'product.images:id,product_id,image_path,public_id',
                    'product.vendor:id,user_id',
                    'product.vendor.user:id,name',
                    'product.vendor.reviews:id,vendor_id,description,rating',
                    'product.category:id,name',
                ])
                ->select('id','key','product_id')
                ->latest()
                ->paginate(10);

            if ($selects->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found under this key', $selects, 200);
            }

            return ResponseHelper::Out('success', 'Products fetched successfully by key', $selects, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
// Get "Just For You" products
    public function getJustForYou(Request $request): JsonResponse
    {
        try {
            $selects = AdminSelect::where('key', 'just_for_you')
                ->with([
                    'product:id,name,regular_price,sell_price,image,category_id,vendor_id',
                    'product.images:id,product_id,image_path,public_id',
                    'product.vendor:id,user_id',
                    'product.vendor.user:id,name',
                    'product.vendor.reviews:id,vendor_id,description,rating',
                    'product.category:id,name',
                ])
                ->select('id','key','product_id')
                ->latest()
                ->paginate(10);
            if ($selects->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found under this key', $selects, 200);
            }
            return ResponseHelper::Out('success', 'Products fetched successfully by key', $selects, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }


    // Store admin select
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|string|max:255',
                'product_id' => 'required',
            ]);
            $adminSelect = AdminSelect::create([
                'key' => $request->input('key'),
                'product_id' => $request->input('product_id'),
            ]);
            return ResponseHelper::Out('success', 'Admin select created successfully', $adminSelect, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Update admin select
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|string|max:255',
                'product_id' => 'required|exists:products,id',
            ]);
            $select = AdminSelect::where('id', $id)->first();

            if (!$select) {
                return ResponseHelper::Out('failed', 'Admin select not found or not owned by vendor', null, 404);
            }
            $select->update([
                'key' => $request->input('key'),
                'product_id' => $request->input('product_id'),
            ]);
            return ResponseHelper::Out('success', 'Admin select updated successfully', $select, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Delete admin select
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $select = AdminSelect::where('id', $id)->first();
            if (!$select) {
                return ResponseHelper::Out('failed', 'Admin select not found or not owned by vendor', null, 404);
            }
            $select->delete();
            return ResponseHelper::Out('success', 'Admin select deleted successfully', null, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
