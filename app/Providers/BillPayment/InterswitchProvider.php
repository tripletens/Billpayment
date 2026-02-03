<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use Illuminate\Support\Facades\Http;

class InterswitchProvider implements BillPaymentProviderInterface
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $secret;
    protected string $terminalId;

    public function __construct()
    {
        $this->baseUrl = config('billpayment.interswitch.base_url');
        $this->clientId = config('billpayment.interswitch.client_id');
        $this->secret = config('billpayment.interswitch.secret');
        $this->terminalId = config('billpayment.interswitch.terminal_id');
    }

    public function vendElectricity(ElectricityVendDTO $dto): array
    {
        // Generate access token (simplified - in production use caching)
        $accessToken = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'TerminalId' => $this->terminalId,
        ])->post($this->baseUrl . '/api/v2/quickteller/payments/advices', [
            'paymentCode' => $this->getPaymentCode($dto->disco),
            'customerId' => $dto->meterNumber,
            'customerMobile' => $dto->phone,
            'customerEmail' => '', // Optional
            'amount' => $dto->amount * 100, // Interswitch uses kobo
            'requestReference' => uniqid('isw_'),
        ]);

        return $response->json() ?? [];
    }

    public function getName(): string
    {
        return 'interswitch';
    }

    protected function getAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($this->baseUrl . '/passport/oauth/token', [
                'grant_type' => 'client_credentials',
            ]);

        return $response->json('access_token') ?? '';
    }

    /**
     * Get the Interswitch payment code for a disco.
     */
    protected function getPaymentCode(string $disco): string
    {
        // These are placeholder codes - actual codes should be obtained from Interswitch
        $mapping = [
            'AEDC' => '10410',
            'EKEDC' => '10420',
            'IKEDC' => '10430',
            'IBEDC' => '10440',
            'JEDC' => '10450',
            'KEDCO' => '10460',
            'KAEDCO' => '10470',
            'PHED' => '10480',
            'EEDC' => '10490',
            'BEDC' => '10500',
        ];

        return $mapping[strtoupper($disco)] ?? '10000';
    }
}
