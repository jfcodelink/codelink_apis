<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class check_login
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            $token = session()->get('token');
            if (Auth::guard('sanctum')->user()) {
                return $next($request);
            } else {
                session()->forget('token');

                abort(403, 'Unauthorize');
            }
        }

        abort(403, 'Unauthorize');
    }
}
