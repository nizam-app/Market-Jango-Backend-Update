<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Weight;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WeightController extends Controller
{
    //GET ALL WEIGHT 
    public function index(): JsonResponse
    {
        try {
            $weights = Weight::select(
                'id',
                'min_weight',
                'max_weight',
                'weight_unit',
                'delivery_charge',
                'status'
            )
                ->paginate(10);

            if ($weights->isEmpty()) {
                return ResponseHelper::Out('success', 'No weight charges found', [], 200);
            }

            return ResponseHelper::Out(
                'success',
                'All weight charges fetched successfully',
                $weights,
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function show($id): JsonResponse
    {
        try {
            $weights = Weight::where('id', $id)->select(
                'id',
                'min_weight',
                'max_weight',
                'weight_unit',
                'delivery_charge',
                'status'
            )
                ->first();

            if (!$weights) {
                return ResponseHelper::Out('success', 'No weight charge found', [], 200);
            }

            return ResponseHelper::Out(
                'success',
                'weight charges fetched successfully',
                $weights,
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // CREATE WEIGHT 
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'min_weight'      => 'required|numeric|min:0',
                'max_weight'      => 'required|numeric|gt:min_weight',
                'delivery_charge' => 'required|numeric|min:0',
                'weight_unit'     => 'nullable|in:kg,gram',
            ]);

            $weight = Weight::create([
                'min_weight'      => $request->min_weight,
                'max_weight'      => $request->max_weight,
                'delivery_charge' => $request->delivery_charge,
                'weight_unit'     => $request->weight_unit ?? 'kg',
                'status'          => true,
            ]);

            return ResponseHelper::Out(
                'success',
                'Weight charge created successfully',
                $weight,
                201
            );
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    //UPDATE WEIGHT 
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $weight = Weight::findOrFail($id);
            $request->validate([
                'min_weight'      => 'nullable|numeric|min:0',
                'max_weight'      => 'nullable|numeric|gt:min_weight',
                'delivery_charge' => 'nullable|numeric|min:0',
                'weight_unit'     => 'nullable|in:kg,gram',
                'status'          => 'nullable|boolean',
            ]);
            $weight->update([
                'min_weight'      => $request->input('min_weight', $weight->min_weight),
                'max_weight'      => $request->input('max_weight', $weight->max_weight),
                'delivery_charge' => $request->input('delivery_charge', $weight->delivery_charge),
                'weight_unit'     => $request->input('weight_unit', $weight->weight_unit),
                'status'          => $request->input('status', $weight->status),
            ]);
            return ResponseHelper::Out(
                'success',
                'Weight charge updated successfully',
                $weight,
                200
            );
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    //DELETE WEIGHT
    public function destroy($id): JsonResponse
    {
        try {
            $weight = Weight::findOrFail($id);
            $weight->delete();

            return ResponseHelper::Out(
                'success',
                'Weight charge deleted successfully',
                null,
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
