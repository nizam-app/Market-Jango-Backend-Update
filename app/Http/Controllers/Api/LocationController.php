<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::with('routes')->get();
        return response()->json(['message' => 'All locations fetched', 'data' => $locations]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'routes' => 'nullable|array',
            'routes.*.id' => 'exists:routes,id',
            'routes.*.sequence' => 'integer'
        ]);

        $location = Location::create(['name' => $request->name]);

        if ($request->has('routes')) {
            $pivot = [];
            foreach ($request->routes as $r) {
                $pivot[$r['id']] = ['sequence' => $r['sequence'] ?? 0];
            }
            $location->routes()->attach($pivot);
        }

        return response()->json(['message' => 'Location created', 'data' => $location->load('routes')], 201);
    }

    public function show($id)
    {
        $location = Location::with('routes')->findOrFail($id);
        return response()->json(['message' => 'Location found', 'data' => $location]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'routes' => 'nullable|array',
            'routes.*.id' => 'exists:routes,id',
            'routes.*.sequence' => 'integer'
        ]);

        $location = Location::findOrFail($id);
        if ($request->name) $location->update(['name' => $request->name]);

        if ($request->has('routes')) {
            $pivot = [];
            foreach ($request->routes as $r) {
                $pivot[$r['id']] = ['sequence' => $r['sequence'] ?? 0];
            }
            $location->routes()->sync($pivot);
        }

        return response()->json(['message' => 'Location updated', 'data' => $location->load('routes')]);
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->routes()->detach();
        $location->delete();
        return response()->json(['message' => 'Location deleted']);
    }
}
