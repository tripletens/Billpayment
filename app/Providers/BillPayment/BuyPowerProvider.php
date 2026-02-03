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
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->baseUrl . '/vend/electricity', [
            'meter_number' => $dto->meterNumber,
            'disco' => $dto->disco,
            'amount' => $dto->amount,
            'phone' => $dto->phone,
            'name' => $dto->customerName,
        ]);

        return $response->json() ?? [];
    }

    public function getName(): string
    {
        return 'buypower';
    }
}
