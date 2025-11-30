<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
//    public function handle($request, Closure $next, $permission)
//    {
//        $user = auth()->user();
//
//        foreach ($user->roles as $role) {
//            if ($role->permissions->contains('name', $permission)) {
//                return $next($request);
//            }
//        }
//
//        return response()->json(['message' => 'Unauthorized'], 403);
//    }
    public function handle($request, Closure $next, $permission)
    {
        $user = auth()->user();

        foreach ($user->roles as $role) {
            if ($role->permissions->contains('name', $permission)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

}
