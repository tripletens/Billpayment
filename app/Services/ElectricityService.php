<?php

namespace App\Services;

use App\DTOs\ElectricityVendDTO;
use App\Factories\BillPaymentProviderFactory;
use App\Repositories\ElectricityRepository;

class ElectricityService
{
    public function __construct(
        protected ElectricityRepository $repository,
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    public function vend(ElectricityVendDTO $dto, ?string $providerName = null)
    {
        // Get the provider (uses default from config if not specified)
        $provider = $this->providerFactory->make($providerName);

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
                'provider' => $provider->getName(),
            ]
        ]);

        try {
            // 2. Use the provider to vend electricity
            $result = $provider->vendElectricity($dto);

            // 3. Update Transaction Status based on response
            if (($result['status'] ?? false) === true || ($result['responseCode'] ?? '') === '00') {
                $transaction->status = 'success';
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

