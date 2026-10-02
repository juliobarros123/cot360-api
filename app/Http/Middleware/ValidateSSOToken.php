<?php

namespace App\Http\Middleware;

use App\Services\SsoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
class ValidateSSOToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token não fornecido'], 401);
        }

        // Verifica cache local primeiro
        $userData = Cache::get("user_auth_{$token}");
// dd($token,$userData);
        if (!$userData) {
            $userData = app(SsoService::class)->getUserData($token);
            if (!$userData) {
                return response()->json(['message' => 'Token inválido'], 401);
            }

            Cache::put("user_auth_{$token}", $userData, now()->addMinutes(30));
        }

        $request->merge(['sso_user' => $userData['user']]);
        return $next($request);
    }
}
