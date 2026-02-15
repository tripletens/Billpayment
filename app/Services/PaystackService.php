<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl = config('services.paystack.base_url');
    }

    /**
     * Initialize a transaction with Paystack.
     */
    public function initializeTransaction(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'] * 100, // Convert to kobo
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'] ?? route('payment.callback'),
                'metadata' => $data['metadata'] ?? [],
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paystack Initialization Error', ['error' => $e->getMessage()]);

            return [
                'status' => false,
                'message' => 'Could not initialize Paystack transaction: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify a transaction with Paystack.
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
            ])->get($this->baseUrl."/transaction/verify/{$reference}");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paystack Verification Error', ['error' => $e->getMessage(), 'reference' => $reference]);

            return [
                'status' => false,
                'message' => 'Could not verify Paystack transaction: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook signature.
     */
    public function isValidWebhook(string $payload, string $signature): bool
    {
        return $signature === hash_hmac('sha512', $payload, $this->secretKey);
    }
}
