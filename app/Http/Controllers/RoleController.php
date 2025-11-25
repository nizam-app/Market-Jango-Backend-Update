<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Services\RoleService;

class RoleController extends Controller
{
    public function index()
    {
        return Role::with(['permissions','users'])->get();
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);

        $role = Role::create(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role
        ]);
    }  //Update role
    public function updateRoles(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);
        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    // Delete role
    public function destroyRoles($id)
    {
        $role = Role::findOrFail($id);
        $role->users()->detach();
        $role->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully'
        ]);
    }
    public function assignPermissions(Request $request, $role_id)
    {
        $request->validate(['permissions' => 'required|array']);

        $role = Role::findOrFail($role_id);

        $role->permissions()->sync($request->permissions);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions assigned successfully',
            'data' => $role->permissions
        ]);
    }
}
