<?php

// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

// class RouteLocationController extends Controller
// {
//     //
// }

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;


class RouteLocationController extends Controller
{
    // Get all Route Locations
    public function index(): JsonResponse
    {
        try {
            $routeLocations = LocationRoute::with(['route', 'startPoint', 'endPoint'])->paginate(10);
                  
            if ($routeLocations->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No route locations found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Route locations fetched successfully',
                $routeLocations
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    // Store new Route Location
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'route_id' => 'required|exists:routes,id',
                'start_point_id' => 'required|exists:locations,id',
                'end_point_id' => 'required|exists:locations,id',
                'price' => 'required|numeric|min:0'
            ]);

            $routeLocation = LocationRoute::create($request->only([
                'route_id',
                'start_point_id',
                'end_point_id',
                'price'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Route location successfully created',
                'data' => $routeLocation
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation exception',
                'data' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    // Show single Route Location
    public function show($id): JsonResponse
    {
        try {
            $routeLocation = LocationRoute::with(['route', 'startPoint', 'endPoint'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Route location fetched successfully',
                'data' => $routeLocation
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    // Update Route Location
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $routeLocation = LocationRoute::findOrFail($id);

            $request->validate([
                'route_id' => 'exists:routes,id',
                'start_point_id' => 'exists:locations,id',
                'end_point_id' => 'exists:locations,id',
                'price' => 'numeric|min:0'
            ]);

            $routeLocation->update($request->only([
                'route_id',
                'start_point_id',
                'end_point_id',
                'price'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Route location successfully updated',
                'data' => $routeLocation
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation exception',
                'data' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    // Delete Route Location
    public function destroy($id): JsonResponse
    {
        try {
            $routeLocation = LocationRoute::findOrFail($id);
            $routeLocation->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Route location successfully deleted',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }
}
