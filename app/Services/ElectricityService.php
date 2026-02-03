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
        // 1. Create local transaction with 'pending' status
        $transaction = $this->repository->storeTransaction([
            'reference' => uniqid('ref_'),
            'type' => 'electricity',
            'amount' => $dto->amount,
            'status' => 'pending',
            'meta' => [
                'meter_number' => $dto->meterNumber,
                'disco' => $dto->disco,
                'phone' => $dto->phone,
                'customer_name' => $dto->customerName,
            ]
        ]);

        try {
            // 2. Call External API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.buypower.token'),
            ])->post(config('services.buypower.base_url') . '/vend/electricity', [
                'meter_number' => $dto->meterNumber,
                'disco' => $dto->disco,
                'amount' => $dto->amount,
                'phone' => $dto->phone,
                'name' => $dto->customerName,
                'reference' => $transaction->reference, // Pass our reference if supported
            ]);

            $result = $response->json();

            // 3. Update Transaction Status based on response
            if ($response->successful() && ($result['status'] ?? false) === true) {
                $transaction->status = 'success';
                // Might want to store external_reference here if available
            } else {
                $transaction->status = 'failed';
            }
            
            // Append result to existing meta
            $meta = json_decode($transaction->meta, true) ?? [];
            $meta['api_response'] = $result;
            $transaction->meta = json_encode($meta);
            
            $transaction->save();
            
            return $transaction;

        } catch (\Exception $e) {
            $transaction->status = 'failed';
            $meta = json_decode($transaction->meta, true) ?? [];
            $meta['error'] = $e->getMessage();
            $transaction->meta = json_encode($meta);
            $transaction->save();
            
            throw $e;
        }
    }
}
