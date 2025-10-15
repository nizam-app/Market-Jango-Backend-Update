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
    public function index(): JsonResponse
    {
        try {
            $attributes = ProductAttribute::with('attributeValues')->get();
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
                'name' => 'required|string|max:20',
                'vendor_id' => 'required|exists:vendors,id'
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id')) ->with('user')->first();
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
            $user = User::where('id', $request->header('id'))
                ->where('email', $request->header('email'))
                ->with('vendor')
                ->first();

            if (!$user || !$user->vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }

            // Find variant + ownership check
            $variant = ProductVariant::where('id', $id)
                ->whereHas('product', function ($q) use ($user) {
                    $q->where('vendor_id', $user->vendor->id);
                })
                ->first();
            if (!$variant) {
                return ResponseHelper::Out('failed', 'Product variant not found or not owned by vendor', null, 404);
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
            // Auth user with vendor
            $user = User::where('id', $request->header('id'))
                ->where('email', $request->header('email'))
                ->with('vendor')
                ->first();
            if (!$user || !$user->vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find variant + ownership check
            $variant = ProductVariant::where('id', $id)
                ->whereHas('product', function ($q) use ($user) {
                    $q->where('vendor_id', $user->vendor->id);
                })
                ->first();
            if (!$variant) {
                return ResponseHelper::Out('failed', 'Product variant not found or not owned by vendor', null, 404);
            }
            $variant->delete();
            return ResponseHelper::Out('success', 'Product variant successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
