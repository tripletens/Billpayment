<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyServerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedToken = $request->header('X-SERVER-TOKEN');
        $validToken = config('services.internal.server_token');

        if (!$providedToken || !hash_equals((string) $validToken, (string) $providedToken)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Invalid internal server token.'
            ], 401);
        }

        return $next($request);
    }
}
