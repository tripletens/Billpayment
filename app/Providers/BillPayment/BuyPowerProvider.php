<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use App\DTOs\MeterCheckResponseDTO;
use App\DTOs\TransactionResponseDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BuyPowerProvider implements BillPaymentProviderInterface
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('billpayment.buypower.base_url');
        $this->token = config('billpayment.buypower.token');
    }

    public function vendElectricity(ElectricityVendDTO $dto): array
    {
        $payload = [
            'orderId' => \Illuminate\Support\Str::uuid()->toString(),
            'meter' => $dto->meterNumber,
            'disco' => $this->mapDisco($dto->disco),
            'phone' => $dto->phone,
            'paymentType' => 'B2B',
            'vendType' => 'PREPAID',
            'vertical' => 'ELECTRICITY',
            'amount' => (string) $dto->amount,
            'email' => $dto->email ?? 'support@lytbills.com',
            'name' => $dto->customerName,
        ];

        // dd($payload);

        Log::info('BuyPower API Request', [
            'url' => $this->baseUrl . '/vend',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->baseUrl . '/vend', $payload);

        $result = $response->json() ?? [];

        Log::info('BuyPower API Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return $result;
    }

    public function checkMeter(string $meter, string $disco, string $vendType): MeterCheckResponseDTO
    {
        $payload = [
            'meter' => $meter,
            'disco' => $this->mapDisco($disco),
            'vendType' => strtoupper($vendType),
        ];

        Log::info('BuyPower Meter Check Request', [
            'url' => $this->baseUrl . '/check/meter',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get($this->baseUrl . '/check/meter', $payload);

        $result = $response->json() ?? [];

        Log::info('BuyPower Meter Check Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return MeterCheckResponseDTO::fromArray($result);
    }

    public function getTransaction(string $orderId): TransactionResponseDTO
    {
        Log::info('BuyPower Get Transaction Request', [
            'url' => $this->baseUrl . '/transaction/' . $orderId,
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get($this->baseUrl . '/transaction/' . $orderId);

        $result = $response->json() ?? [];

        Log::info('BuyPower Get Transaction Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        // Handle nested response structure: result.data
        $transactionData = $result['data'] ?? $result['result']['data'] ?? $result;

        return TransactionResponseDTO::fromArray($transactionData);
    }

    protected function mapDisco(string $disco): string
    {
        $mapping = [
            'AEDC' => 'ABUJA',
            'EKEDC' => 'EKO',
            'IKEDC' => 'IKEJA',
            'IBEDC' => 'IBADAN',
            'JEDC' => 'JOS',
            'KEDCO' => 'KANO',
            'KAEDCO' => 'KADUNA',
            'PHED' => 'PORTHARCOURT',
            'EEDC' => 'ENUGU',
            'BEDC' => 'BENIN',
        ];

        return $mapping[strtoupper($disco)] ?? strtoupper($disco);
    }

    public function getName(): string
    {
        return 'buypower';
    }
}
