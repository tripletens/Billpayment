<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use Illuminate\Support\Facades\Http;

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

        \Log::info('BuyPower API Request', [
            'url' => $this->baseUrl . '/vend',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->baseUrl . '/vend', $payload);

        $result = $response->json() ?? [];

        \Log::info('BuyPower API Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return $result;
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
