<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function assignRole(Request $request, $user_id)
    {
        $request->validate(['role_id' => 'required']);
        $user = User::findOrFail($user_id);
        $user->roles()->sync([$request->role_id]);
        return response()->json([
            'status' => 'success',
            'message' => 'Role assigned to user'
        ]);
    }
    public function removeRole($user_id)
    {
        $user = User::findOrFail($user_id);
        $user->roles()->sync([]);
        return response()->json([
            'status' => 'success',
            'message' => 'User now has no role'
        ]);
    }

    public function getUserPermissions($user_id)
    {
        $user = User::findOrFail($user_id);

        $permissions = [];

        foreach ($user->roles as $role) {
            foreach ($role->permissions as $perm) {
                $permissions[] = $perm->name;
            }
        }

        return response()->json([
            'user_roles' => $user->roles,
            'permissions' => array_unique($permissions)
        ]);
    }}
