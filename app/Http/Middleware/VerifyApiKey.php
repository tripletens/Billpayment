<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the API key from the request header
        $providedKey = $request->header('X-API-KEY');
        $validKey = config('services.lytepay.api_key');

        // LYTEPAY_API_KEY=test_api_key
        // LYTEPAY_SECRET=test_secret_101

        // If no key or invalid key, reject
        // Use hash_equals to prevent timing attacks
        if (! $providedKey || ! hash_equals((string) $validKey, (string) $providedKey)) {
            return $this->error('Unauthorized: Invalid API key.', 401);
        }

        return $next($request);
    }
}
