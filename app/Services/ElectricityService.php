<?php

namespace App\Services;

use App\DTOs\ElectricityVendDTO;
use App\Repositories\ElectricityRepository;
use Illuminate\Support\Facades\Http;

class ElectricityService
{
    public function __construct(
        protected ElectricityRepository $repository
    ) {}

    public function vend(ElectricityVendDTO $dto)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.buypower.token'),
        ])->post(config('services.buypower.base_url') . '/vend/electricity', [
            'meter_number' => $dto->meterNumber,
            'disco' => $dto->disco,
            'amount' => $dto->amount,
            'phone' => $dto->phone,
            'name' => $dto->customerName,
        ]);

        $result = $response->json();
        return $this->repository->storeTransaction($result);
    }
}
