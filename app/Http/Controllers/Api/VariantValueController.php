<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Models\VariantValue;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class VariantValueController extends Controller
{
    // Get All Variant Values
    public function index(): JsonResponse
    {
        try {
            $attributeValue = AttributeValue::with('productAttribute')->get();
            return ResponseHelper::Out('success', 'All variant values successfully fetched', $attributeValue, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Variant Value
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:20',
                'product_variant_id' => 'required|exists:product_variants,id'
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->with('user')->paginate(20);
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Check variant ownership via product → vendor
            $variant = ProductAttribute::where('id', $request->product_variant_id)
                ->whereHas('product', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                })
                ->first();

            if (!$variant) {
                return ResponseHelper::Out('failed', 'Product variant not found or not owned by vendor', null, 404);
            }

            $value = VariantValue::create([
                'name' => $request->input('name'),
                'product_variant_id' => $variant->id,
            ]);
            return ResponseHelper::Out('success', 'Variant value successfully created', $value, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // Update Variant Value
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:20',
            ]);

            // Auth user with vendor
            $user = User::where('id', $request->header('id'))
                ->where('email', $request->header('email'))
                ->with('vendor')
                ->first();

            if (!$user || !$user->vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }

            // Find variant value with ownership check
            $value = VariantValue::where('id', $id)
                ->whereHas('productVariant.product', function ($q) use ($user) {
                    $q->where('vendor_id', $user->vendor->id);
                })
                ->first();

            if (!$value) {
                return ResponseHelper::Out('failed', 'Variant value not found or not owned by vendor', null, 404);
            }

            $value->update([
                'name' => $request->input('name'),
            ]);

            return ResponseHelper::Out('success', 'Variant value successfully updated', $value, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // Delete Variant Value
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Auth user with vendor
            $user = User::where('id', $request->header('id'))
                ->where('email', $request->header('email'))
                ->with('vendor')
                ->first();
            if (!$user || !$user->vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find variant value with ownership check
            $value = VariantValue::where('id', $id)
                ->whereHas('productVariant.product', function ($q) use ($user) {
                    $q->where('vendor_id', $user->vendor->id);
                })
                ->first();
            if (!$value) {
                return ResponseHelper::Out('failed', 'Variant value not found or not owned by vendor', null, 404);
            }
            $value->delete();
            return ResponseHelper::Out('success', 'Variant value successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
