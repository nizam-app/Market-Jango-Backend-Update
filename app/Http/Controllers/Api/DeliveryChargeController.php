<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCharge;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryChargeController extends Controller
{
    // Get All Delivery Charges
    public function index(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $deliveryCharges = DeliveryCharge::where('vendor_id',$vendor->id)->select('id','delivery_charge','vendor_id','quantity')->get();
            if ($deliveryCharges == null ) {
                return ResponseHelper::Out('success', 'delivery charge not found', null, 404);
            }
            return ResponseHelper::Out('success', 'All delivery charge successfully fetched', $deliveryCharges, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //single delivery charge
    public function show(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $deliveryCharge = DeliveryCharge::where('vendor_id',$vendor->id)->where('id', $request->input('id'))->select('id','delivery_charge','vendor_id','quantity')->first();
            if ($deliveryCharge == null ) {
                return ResponseHelper::Out('success', 'delivery charge not found', null, 404);
            }
            return ResponseHelper::Out('success', 'delivery charge successfully fetched', $deliveryCharge, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Delivery Charge
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|max:50',
                'delivery_charge' => 'required|max:50'
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $deliveryCharge = DeliveryCharge::create([
                'quantity' => $request->input('quantity'),
                'delivery_charge' => $request->input('delivery_charge'),
                'vendor_id' => $vendor->id,
            ]);
            return ResponseHelper::Out('success', 'delivery Charge successfully created', $deliveryCharge, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Update Delivery Charge
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|max:50',
                'delivery_charge' => 'required|max:50'
            ]);
            // Auth user with vendor
            $vendor = Vendor::where('user_id', $request->header('id'))->select(['id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Find attribute check vendor
            $deliveryCharge = DeliveryCharge::where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->first();
            if (!$deliveryCharge) {
                return ResponseHelper::Out('failed', 'Delivery charge not found', null, 404);
            }
            $deliveryCharge->update([
                'quantity' => $request->input('quantity') ?? $deliveryCharge->quantity,
                'delivery_charge' => $request->input('delivery_charge') ?? $deliveryCharge->delivery_charge,
            ]);
            return ResponseHelper::Out('success', 'Delivery charge successfully updated', $deliveryCharge, 200);
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
            $deliveryCharge = DeliveryCharge::where('id', $id)
                ->where('vendor_id', $vendor->id)
                ->first();
            if (!$deliveryCharge) {
                return ResponseHelper::Out('failed', 'delivery charge not found or not owned by vendor', null, 404);
            }
            $deliveryCharge->delete();
            return ResponseHelper::Out('success', 'delivery charge successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
