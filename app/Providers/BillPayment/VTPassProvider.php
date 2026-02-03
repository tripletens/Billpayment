<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
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

    public function getName(): string
    {
        return 'vtpass';
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
