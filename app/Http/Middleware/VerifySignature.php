<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.lytepay.secret');
        $signature = $request->header('X-Signature');
        $payload = $request->getContent();

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if ($signature !== $expectedSignature) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request signature.'
            ], 403);
        }
        
        return $next($request);
    }
}
