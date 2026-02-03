<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    protected $baseUrl;
    protected $apiKey;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.payment_gateway.base_url');
        $this->apiKey = config('services.payment_gateway.api_key');
        $this->secretKey = config('services.payment_gateway.secret_key');
    }

    public function vendElectricity(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/vend-electricity', $data);

            return $response->json();
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Payment gateway service unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }
}   