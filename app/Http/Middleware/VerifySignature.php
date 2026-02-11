<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySignature
{
    use ApiResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.lytepay.secret');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $payload = $request->getContent();

        if (!$signature || !$timestamp) {
            return $this->error('Missing signature or timestamp headers.', 400);
        }

        // Prevent Replay Attacks: Check if timestamp is within 5 minutes
        if (abs(time() - $timestamp) > 300) {
            return $this->error('Request timestamp expired.', 403);
        }

        // Verify Signature
        // Signature = HMAC_SHA256(payload + timestamp, secret) to bind timestamp to the request
        // Note: Ideally the timestamp should be part of the signature generation on the client side.
        // Assuming current implementation just signs the payload:
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, (string) $signature)) {
            return $this->error('Invalid request signature.', 403);
        }
        
        return $next($request);
    }
}
