<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyServerToken
{
    use ApiResponseTrait;

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
            return $this->error('Unauthorized: Invalid internal server token.', 401);
        }

        return $next($request);
    }
}
