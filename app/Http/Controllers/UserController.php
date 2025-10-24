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
            return ResponseHelper::Out('success', 'All banners successfully fetched', $users, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
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
}
