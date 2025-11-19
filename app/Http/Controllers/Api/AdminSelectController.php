<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminSelect;
use App\Models\Product;
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
                'product',
                'product.images:id,product_id,image_path,public_id',
                'product.vendor',
                'product.vendor.user',
                'product.vendor.reviews',
                'product.category',
            ])
                ->select('id','key','product_id')
                ->latest()
                ->paginate(10);

            if ($selects->isEmpty()) {
                return ResponseHelper::Out('success', 'No admin select view  found under this key', $selects, 200);
            }

            return ResponseHelper::Out('success', 'admin select view  fetched successfully by key', $selects, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
//    //get top product
//    public function getTopProduct(Request $request): JsonResponse
//    {
//        try {
//            $selects = AdminSelect::where('key', 'top_product')
//              ->with([
//                  'product',
//                  'product.images:id,product_id,image_path,public_id',
//                  'product.vendor',
//                  'product.vendor.user',
//                  'product.vendor.reviews',
//                  'product.category',
//              ])
//                  ->select('id','key','product_id')
//                ->latest()
//                  ->paginate(10);
//
//            if ($selects->isEmpty()) {
//                return ResponseHelper::Out('success', 'No products found under this key', $selects, 200);
//            }
//
//            return ResponseHelper::Out('success', 'Top products fetched successfully by key', $selects, 200);
//
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }
//    public function getTopCategory(Request $request): JsonResponse
//    {
//        try {
//            $selects = AdminSelect::where('key', 'top_category')
//              ->with([
//                  'product',
//                  'product.images:id,product_id,image_path,public_id',
//                  'product.vendor',
//                  'product.vendor.user',
//                  'product.vendor.reviews',
//                  'product.category',
//              ])
//                  ->select('id','key','product_id')
//                ->latest()
//                  ->paginate(10);
//
//            if ($selects->isEmpty()) {
//                return ResponseHelper::Out('success', 'No category found under this key', $selects, 200);
//            }
//
//            return ResponseHelper::Out('success', 'Top category fetched successfully by key', $selects, 200);
//
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }
//    // Get new items
//    public function getNewItem(Request $request): JsonResponse
//    {
//        try {
//            $selects = AdminSelect::where('key', 'new_item')
//                ->with([
//                    'product',
//                    'product.images:id,product_id,image_path,public_id',
//                    'product.vendor',
//                    'product.vendor.user',
//                    'product.vendor.reviews',
//                    'product.category',
//                ])
//                ->select('id','key','product_id')
//                ->latest()
//                ->paginate(10);
//
//            if ($selects->isEmpty()) {
//                return ResponseHelper::Out('success', 'No new item found under this key', $selects, 200);
//            }
//
//            return ResponseHelper::Out('success', 'Top new item fetched successfully by key', $selects, 200);
//
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }
//// Get "Just For You" products
//    public function getJustForYou(Request $request): JsonResponse
//    {
//        try {
//            $selects = AdminSelect::where('key', 'just_for_you')
//                ->with([
//                    'product',
//                    'product.images:id,product_id,image_path,public_id',
//                    'product.vendor',
//                    'product.vendor.user',
//                    'product.vendor.reviews',
//                    'product.category',
//                ])
//                ->select('id','key','product_id')
//                ->latest()
//                ->paginate(10);
//            if ($selects->isEmpty()) {
//                return ResponseHelper::Out('success', 'No just for you  found under this key', $selects, 200);
//            }
//            return ResponseHelper::Out('success', 'just for you  fetched successfully by key', $selects, 200);
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }


    //update admin select
    public function adminSelectUpdate(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'new_item' => 'nullable',
                'just_for_you' => 'nullable',
                'top_product' => 'nullable',
            ]);
            $product = Product::where('id', $id)->first();

            $product->update([
                'new_item' => $request->has('new_item') ? $request->input('new_item') : $product->new_item,
                'just_for_you' => $request->has('just_for_you') ? $request->input('just_for_you') : $product->just_for_you,
                'top_product' => $request->has('top_product') ? $request->input('top_product') : $product->top_product,
            ]);

            return ResponseHelper::Out(
                'success',
                'Admin select saved',
                $product,
                200
            );
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
