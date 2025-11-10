<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //all user
    public function index(): JsonResponse
    {
        try {
            $users = User::with('vendor','buyer','driver','transport')
                ->select(['id','name','image','email','phone','user_type','language','status','phone_verified_at'])
                ->paginate(20);
            if($users->isEmpty()){
                return ResponseHelper::Out('success', 'User not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All users successfully fetched', $users, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Get Single User
    public function show(Request $request): JsonResponse
    {
        try {
            $user = User::where('id', $request->input('id'))
                ->with('vendor', 'buyer', 'driver', 'transport')
                ->select(['id', 'name', 'image', 'email', 'phone', 'user_type', 'language', 'status', 'phone_verified_at'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            return ResponseHelper::Out('success', 'User data fetched successfully', $user, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Get Login User
    public function userDetail(Request $request): JsonResponse
    {
        try {
            $userId = $request->header('id');
            $user = User::where('id', $userId)
                ->select(['id'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'user not found', null, 404);
            }
            $user = User::where('id', $user->id)
                ->with('vendor', 'buyer', 'driver', 'transport')
                ->select(['id', 'name', 'image', 'email', 'phone', 'user_type', 'language', 'status', 'phone_verified_at'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            return ResponseHelper::Out('success', 'User data fetched successfully', $user, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function assignRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($request->roles);
        $user->load('roles', 'permissions');

        return response()->json([
            'message' => 'Roles assigned successfully',
            'data' => $user,
        ]);
    }
    public function assignPermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->syncPermissions($request->permissions);
        $user->load('roles', 'permissions');

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'data' => $user,
        ]);
    }
}
