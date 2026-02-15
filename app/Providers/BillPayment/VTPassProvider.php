<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use App\DTOs\TransactionResponseDTO;
use Illuminate\Support\Facades\Http;

class VTPassProvider implements BillPaymentProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = config('billpayment.vtpass.base_url');
        $this->apiKey = config('billpayment.vtpass.api_key');
        $this->secret = config('billpayment.vtpass.secret');
    }

    public function vendElectricity(ElectricityVendDTO $dto): array
    {
        $response = Http::withBasicAuth($this->apiKey, $this->secret)
            ->post($this->baseUrl . '/pay', [
                'request_id' => uniqid('vtpass_'),
                'serviceID' => $this->mapDisco($dto->disco),
                'billersCode' => $dto->meterNumber,
                'variation_code' => 'prepaid', // or 'postpaid'
                'amount' => $dto->amount,
                'phone' => $dto->phone,
            ]);

        return $response->json() ?? [];
    }

    public function checkMeter(string $meter, string $disco, string $vendType): \App\DTOs\MeterCheckResponseDTO
    {
        // VTPass meter check implementation
        $response = Http::withBasicAuth($this->apiKey, $this->secret)
            ->post($this->baseUrl . '/merchant-verify', [
                'billersCode' => $meter,
                'serviceID' => $this->mapDisco($disco),
                'variation_code' => strtolower($vendType) === 'prepaid' ? 'prepaid' : 'postpaid',
            ]);

        $result = $response->json() ?? [];
        return \App\DTOs\MeterCheckResponseDTO::fromArray($result);
    }

    public function getTransaction(string $orderId): TransactionResponseDTO
    {
        // VTPass transaction retrieval implementation
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
            'responseMessage' => 'Transaction status not available from VTPass',
        ];
        return TransactionResponseDTO::fromArray($response);
    }

    public function getName(): string
    {
        return 'vtpass';
    }

    public function getReliabilityIndex(): array
    {
        return [];
    }

    /**
     * Map disco codes from internal format to VTPass service IDs.
     */
    protected function mapDisco(string $disco): string
    {
        $mapping = [
            'AEDC' => 'abuja-electric',
            'EKEDC' => 'eko-electric',
            'IKEDC' => 'ikeja-electric',
            'IBEDC' => 'ibadan-electric',
            'JEDC' => 'jos-electric',
            'KEDCO' => 'kano-electric',
            'KAEDCO' => 'kaduna-electric',
            'PHED' => 'portharcourt-electric',
            'EEDC' => 'enugu-electric',
            'BEDC' => 'benin-electric',
        ];

        return $mapping[strtoupper($disco)] ?? strtolower($disco) . '-electric';
    }
}
