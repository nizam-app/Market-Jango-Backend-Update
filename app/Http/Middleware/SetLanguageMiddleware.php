<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user_id = $request->header('id');
        if (!$user_id) {
            app()->setLocale('en');
            return $next($request);
        }

        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return ResponseHelper::Out('failed', 'User not found', null, 404);
        }
        //if have user language set this language otherwise set english
        $language = $user->language ?? 'en';
        app()->setLocale($language);
        return $next($request);
    }

//    public function handle(Request $request, Closure $next): Response
//    {
//        $user_id = $request->header('id');
//        $user = User::where('id', '=', $user_id)->first();
//        if (!$user) {
//            return ResponseHelper::Out('failed', 'User not found', null, 404);
//        } else{
//            $language = $user->language ?? 'en';
//            app()->setLocale($language);
//        }
//            app()->setLocale('en');
//        return $next($request);
//    }
}
