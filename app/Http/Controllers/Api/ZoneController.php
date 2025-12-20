<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;


class ZoneController extends Controller
{
    // Get All Zones
    public function index(): JsonResponse
    {
        try {
            $zones = Zone::select(
                'id',
                'name',
                'center_latitude',
                'center_longitude',
                'radius_km',
                'price',
                'status'
            )
            ->paginate(10); 

            if ($zones->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no zones', [], 200);
            }
       
            return ResponseHelper::Out('success', 'All zones successfully fetched', $zones, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // // Store Zone
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'center_latitude' => 'required|numeric',
                'center_longitude' => 'required|numeric',
                'radius_km' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'status' => 'in:Active,Inactive'
            ]);
            $zone = Zone::create([
                'name' => $request->input('name'),
                'center_latitude' => $request->input('center_latitude'),
                'center_longitude' => $request->input('center_longitude'),
                'radius_km' => $request->input('radius_km'),
                'price' => $request->input('price'),
                'status' => $request->input('status', 'Active'),
            ]);
            return ResponseHelper::Out(
                'success',
                'Zone successfully created',
                $zone,
                201
            );
        } catch (ValidationException $e) {
            return ResponseHelper::Out(
                'failed',
                'Validation exception',
                $e->errors(),
                422
            );
        } catch (Exception $e) {
            return ResponseHelper::Out(
                'failed',
                'Something went wrong',
                $e->getMessage(),
                500
            );
        }
    }
    // // Update Zone
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $zone = Zone::findOrFail($id);
            $request->validate([
                'name' => 'string|max:50|unique:zones,name,' . $id,
                'center_latitude' => 'numeric',
                'center_longitude' => 'numeric',
                'radius_km' => 'numeric|min:0',
                'price' => 'numeric|min:0',
                'status' => 'in:Active,Inactive'
            ]);
            $zone->update([
                'name' => $request->name ?? $zone->name,
                'center_latitude' => $request->center_latitude ?? $zone->center_latitude,
                'center_longitude' => $request->center_longitude ?? $zone->center_longitude,
                'radius_km' => $request->radius_km ?? $zone->radius_km,
                'price' => $request->price ?? $zone->price,
                'status' => $request->status ?? $zone->status,
            ]);
            return ResponseHelper::Out('success', 'Zone successfully updated', $zone, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // // Delete Zone
    public function destroy($id): JsonResponse
    {
        try {
            $zone = Zone::findOrFail($id);
            $zone->delete();
            return ResponseHelper::Out('success', 'Zone successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // // Show single Zone
    public function show($id): JsonResponse
    {
        try {
            $zone = Zone::where('id', $id)->select(
                'id',
                'name',
                'center_latitude',
                'center_longitude',
                'radius_km',
                'price',
                'status'
            )->paginate(10);
            return ResponseHelper::Out('success', 'Zone fetched successfully', $zone, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
