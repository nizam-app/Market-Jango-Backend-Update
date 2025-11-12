<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return ResponseHelper::Out('success', 'Roles retrieved successfully', $roles, 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions') && count($request->permissions) > 0) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');
        return ResponseHelper::Out('success', 'Roles created successfully', $role, 201);
    }
    public function show(Role $role)
    {
        $role->load('permissions');
        return ResponseHelper::Out('success', 'Roles retrieved successfully', $role, 200);
    }
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        return ResponseHelper::Out('success', 'Roles update successfully', $role, 200);

    }
    public function destroy(Role $role)
    {
        $role->delete();
        return ResponseHelper::Out('success', 'Roles deleted successfully', $role, 200);
    }
}
