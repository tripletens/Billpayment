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
        // Verify Signature
        if ($request->isMethod('get')) {
            $payload = $request->getQueryString();
        } else {
            $payload = $request->getContent();
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        \Log::info('Signature Debug', [
            'provided' => $signature,
            'expected' => $expectedSignature,
            'payload' => $payload,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        if (! hash_equals($expectedSignature, (string) $signature)) {
            return $this->error('Invalid request signature.', 403);
        }

        return $next($request);
    }
}
