<?php

namespace App\Http\Middleware;

use App\Helpers\JWTToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenVerifyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->header('token');
        $result = JWTToken::VerifyToken($bearerToken);
        if (isset($result['status']) && $result['status'] === "failed") {
            return response()->json([
                $result['status'],
                $result['message']
            ],401);
        } else if(!$result){
            return response()->json([
                'status' => 'Fail',
                'message' => 'unauthorized'
            ], 401);
        }
        $request->headers->set('user_type', $result['userType']);
        $request->headers->set('id', $result['userId']);
        return $next($request);
    }
}
