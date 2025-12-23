<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\SetWeight;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SetWeightController extends Controller
{

// GET ALL WEIGHTS
    public function index(): JsonResponse
    {
        try {
            $weights = SetWeight::select('id', 'max_weight', 'weight_unit', 'status')
                ->paginate(10);

            if ($weights->isEmpty()) {
                return ResponseHelper::Out('success', 'No weight slabs found', [], 200);
            }

            return ResponseHelper::Out(
                'success',
                'All weight slabs fetched successfully',
                $weights,
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // GET SINGLE WEIGHT
    public function show($id): JsonResponse
    {
        try {
            $weight = SetWeight::select('id', 'max_weight', 'weight_unit', 'status')
                ->find($id);

            if (!$weight) {
                return ResponseHelper::Out('success', 'Weight slab not found', [], 200);
            }

            return ResponseHelper::Out(
                'success',
                'Weight slab fetched successfully',
                $weight,
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
                'max_weight' => 'required|numeric|min:0',
                'weight_unit' => 'nullable|in:kg,gram',
            ]);

            $weight = SetWeight::create([
                'max_weight' => $request->max_weight,
                'weight_unit' => $request->weight_unit ?? 'kg',
                'status' => true,
            ]);

            return ResponseHelper::Out(
                'success',
                'Weight slab created successfully',
                $weight,
                201
            );
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // UPDATE WEIGHT
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $weight = SetWeight::findOrFail($id);

            $request->validate([
                'max_weight' => 'nullable|numeric|min:0',
                'weight_unit' => 'nullable|in:kg,gram',
                'status' => 'nullable|boolean',
            ]);

            $weight->update([
                'max_weight' => $request->input('max_weight', $weight->max_weight),
                'weight_unit' => $request->input('weight_unit', $weight->weight_unit),
                'status' => $request->input('status', $weight->status),
            ]);

            return ResponseHelper::Out(
                'success',
                'Weight slab updated successfully',
                $weight,
                200
            );
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // DELETE WEIGHT
    public function destroy($id): JsonResponse
    {
        try {
            $weight = SetWeight::findOrFail($id);
            $weight->delete();

            return ResponseHelper::Out(
                'success',
                'Weight slab deleted successfully',
                null,
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
