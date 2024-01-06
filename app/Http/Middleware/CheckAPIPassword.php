<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAPIPassword
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $password = $request->header('API-Password');

        if ($password !== '!@#$%^&*()AaSsDd741') {
            return response()->json(['error' => 'Invalid API password'], 401);
        }
        return $next($request);
    }
}
