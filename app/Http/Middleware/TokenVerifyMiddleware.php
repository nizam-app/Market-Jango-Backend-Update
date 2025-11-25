<?php

namespace App\Http\Middleware;

use App\Helpers\JWTToken;
use App\Jobs\MarkUserOffline;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

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
        $userId = $result['userId'];
        $request->headers->set('user_type', $result['userType'] ?? null);
        $request->headers->set('id', $userId);
        $request->headers->set('email', $result['userEmail'] ?? null);
        if ($userId) {
            User::where('id', $userId)->update([
                'is_online' => true,
                'last_active_at' => now()
            ]);

            // Dispatch job to mark offline after 60 sec
            MarkUserOffline::dispatch($userId)->delay(now()->addSeconds(60));
        }
        return $next($request);
    }
}
