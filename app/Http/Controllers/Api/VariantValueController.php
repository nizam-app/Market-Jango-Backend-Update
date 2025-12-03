<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class VariantValueController extends Controller
{
    // Get All Variant Values
    public function allAttributeValues(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $attributeValue = AttributeValue::where('vendor_id', $vendor->id)->with(['productAttribute:id,name'])->select('id', 'name','product_attribute_id' )->get();
            return ResponseHelper::Out('success', 'All attribute values successfully fetched', $attributeValue, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // CREATE ATTRIBUTE VALUE
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:20',
                'product_attribute_id' => 'required'
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $value = AttributeValue::create([
                'name' => $request->input('name'),
                'product_attribute_id' => $request->input('product_attribute_id'),
                'vendor_id' => $vendor->id
            ]);
            return ResponseHelper::Out('success', 'Variant value successfully created', $value, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // UPDATE ATTRIBUTE VALUE
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'nullable|string|max:20'
            ]);
            // authentication vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find variant value with ownership check
            $attributeValue = AttributeValue::where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->first();
            if (!$attributeValue) {
                return ResponseHelper::Out('failed', 'Attribute value not found or not owned by vendor', null, 404);
            }
            $attributeValue->update([
                'name' => $request->input('name')?? $attributeValue->name
            ]);
            return ResponseHelper::Out('success', 'Attribute value successfully updated', $attributeValue, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // DELETE ATTRIBUTE VALUE
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find variant value with ownership check
            $value = AttributeValue::where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->first();
            if (!$value) {
                return ResponseHelper::Out('failed', 'Attribute value not found', null, 404);
            }
            $value->delete();
            return ResponseHelper::Out('success', 'Attribute value successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
