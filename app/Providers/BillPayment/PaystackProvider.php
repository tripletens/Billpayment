<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use App\DTOs\TransactionResponseDTO;
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

    public function checkMeter(string $meter, string $disco, string $vendType): \App\DTOs\MeterCheckResponseDTO
    {
        // Paystack meter check implementation
        // Note: Paystack may not have a direct meter check endpoint
        // This is a placeholder that returns a default response
        $response = [
            'error' => false,
            'discoCode' => strtoupper($disco),
            'vendType' => strtoupper($vendType),
            'meterNo' => $meter,
            'minVendAmount' => 500,
            'maxVendAmount' => 50000,
            'responseCode' => 100,
            'outstanding' => 0,
            'debtRepayment' => 0,
            'name' => 'Customer',
            'address' => 'Address not available',
            'tariff' => 'Not available',
            'tariffClass' => 'Not available',
        ];
        return \App\DTOs\MeterCheckResponseDTO::fromArray($response);
    }

    public function getTransaction(string $orderId): TransactionResponseDTO
    {
        // Paystack transaction retrieval implementation
        // Placeholder that returns default response structure
        $response = [
            'id' => 0,
            'amountGenerated' => 0,
            'disco' => 'N/A',
            'debtAmount' => 0,
            'debtRemaining' => 0,
            'orderId' => $orderId,
            'receiptNo' => 'N/A',
            'tax' => 0,
            'vendTime' => now()->toIso8601String(),
            'token' => 'N/A',
            'totalAmountPaid' => 0,
            'units' => 0,
            'vendAmount' => 0,
            'vendRef' => $orderId,
            'responseCode' => 'PENDING',
            'responseMessage' => 'Transaction status not available from Paystack',
        ];
        return TransactionResponseDTO::fromArray($response);
    }

    public function getName(): string
    {
        return 'paystack';
    }

    public function getReliabilityIndex(): array
    {
        return [];
    }
}
