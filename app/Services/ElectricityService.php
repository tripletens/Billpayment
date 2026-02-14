<?php

namespace App\Services;

use App\DTOs\ElectricityVendDTO;
use App\Factories\BillPaymentProviderFactory;
use App\Repositories\ElectricityRepository;
use Illuminate\Support\Facades\Log;

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

        Log::info('Electricity Vending Started', [
            'meter' => $dto->meterNumber,
            'amount' => $dto->amount,
            'provider' => $provider->getName()
        ]);

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
            ],
        ]);

        try {
            // 2. Use the provider to vend electricity
            $result = $provider->vendElectricity($dto);

            // dd($result);

            // 3. Update Transaction Status based on response
            if (($result['status'] ?? false) === true || ($result['responseCode'] ?? '') === '00' || ($result['responseCode'] ?? 0) == 200) {
                $transaction->status = 'success';
            } else {
                $transaction->status = 'failed';
            }

            Log::info('Electricity Vending Completed', [
                'reference' => $transaction->reference,
                'status' => $transaction->status,
                'response_message' => $result['message'] ?? 'No message'
            ]);

            // Append result to existing meta
            $meta = $transaction->meta ?? [];
            $meta['api_response'] = $result;
            $transaction->meta = $meta;

            $transaction->save();

            return $transaction;

        } catch (\Exception $e) {
            Log::error('Electricity Vending Error', [
                'meter' => $dto->meterNumber,
                'error' => $e->getMessage()
            ]);

            $transaction->status = 'failed';
            $meta = $transaction->meta ?? [];
            $meta['error'] = $e->getMessage();
            $transaction->meta = $meta;
            $transaction->save();

            throw $e;
        }
    }
}
