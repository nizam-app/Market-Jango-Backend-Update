<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();

        return response()->json([
            'message' => 'Permissions retrieved successfully',
            'data' => $permissions,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'description' => $request->description ?? null,
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'data' => $permission,
        ], 201);
    }
    public function show(Permission $permission)
    {
        return response()->json([
            'message' => 'Permission retrieved successfully',
            'data' => $permission,
        ]);
    }
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'string|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string',
        ]);

        $permission->update($request->only('name', 'description'));

        return response()->json([
            'message' => 'Permission updated successfully',
            'data' => $permission,
        ]);
    }
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully',
        ]);
    }
}
