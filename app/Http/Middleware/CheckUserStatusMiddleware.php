<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->headers->get('id');
        if(!$userId){
            return ResponseHelper::Out('failed','Id not found',null, 404);
        }
        $user = User::where('id', $userId)->first();
        if(!$user){
            return ResponseHelper::Out('failed','User not found',null, 404);
        }
        if ($user->status !== 'Approved') {
            return ResponseHelper::Out('success','Your account is not approved yet',null, 200);
        }

        return $next($request);
    }
}
