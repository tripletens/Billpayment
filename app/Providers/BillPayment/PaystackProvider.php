<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use Illuminate\Support\Facades\Http;

class PaystackProvider implements BillPaymentProviderInterface
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        // Assuming paystack config exists in services.php or a dedicated config
        $this->baseUrl = 'https://api.paystack.co';
        $this->secretKey = config('services.paystack.secret_key');
    }

    public function vendElectricity(ElectricityVendDTO $dto): array
    {
        // Mocking Paystack Electricity Vending logic
        // In a real scenario, this would call Paystack's specialized endpoints if they exist
        // or a custom integration via their payment pages/APIs.
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
        ])->post($this->baseUrl . '/billpayment/electricity', [
            'meter_number' => $dto->meterNumber,
            'disco' => $dto->disco,
            'amount' => $dto->amount * 100, // Paystack uses kobo
            'phone' => $dto->phone,
            'email' => 'customer@example.com', // Required by Paystack often
        ]);

        return $response->json() ?? [];
    }

    public function getName(): string
    {
        return 'paystack';
    }
}
