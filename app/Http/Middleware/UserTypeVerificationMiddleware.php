<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserTypeVerificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$userTypeVerify): Response
    {
        $userId = $request->headers->get('id');
        if (!$userId) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Unauthorized'
            ], 401);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User not found'
            ], 404);
        }
        // Check if user role is allowed
        if (!in_array($user->user_type, $userTypeVerify)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Forbidden: You do not have access to this resource'
            ], 403);
        }
        return $next($request);
    }
}
