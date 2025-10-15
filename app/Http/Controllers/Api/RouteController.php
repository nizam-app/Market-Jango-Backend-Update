<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::with('locations')->get();
        return response()->json(['message' => 'All routes fetched', 'data' => $routes]);
    }

    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'locations' => 'required|array',
                'locations.*.id' => 'exists:locations,id',
                'locations.*.sequence' => 'integer'
            ]);

            $route = Route::create(['name' => $request->name]);

            $pivot = [];
            foreach ($request->locations as $location) {
                $pivot[$location['id']] = ['sequence' => $location['sequence'] ?? 0];
            }
            $route->locations()->attach($pivot);
            return ResponseHelper::Out('success','Route created',$route->load('locations'), 201);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }

    public function show($id)
    {
        $route = Route::with('locations')->findOrFail($id);
        return response()->json(['message' => 'Route found', 'data' => $route]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'locations' => 'nullable|array',
            'locations.*.id' => 'exists:locations,id',
            'locations.*.sequence' => 'integer'
        ]);

        $route = Route::findOrFail($id);
        if ($request->name) $route->update(['name' => $request->name]);

        if ($request->has('locations')) {
            $pivot = [];
            foreach ($request->locations as $loc) {
                $pivot[$loc['id']] = ['sequence' => $loc['sequence'] ?? 0];
            }
            $route->locations()->sync($pivot);
        }

        return response()->json(['message' => 'Route updated', 'data' => $route->load('locations')]);
    }

    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->locations()->detach();
        $route->delete();
        return response()->json(['message' => 'Route deleted']);
    }

}
