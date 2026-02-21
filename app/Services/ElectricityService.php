<?php

namespace App\Services;

use App\DTOs\ElectricityVendDTO;
use App\Factories\BillPaymentProviderFactory;
use App\Models\Transaction;
use App\Repositories\ElectricityRepository;
use Illuminate\Support\Facades\Log;

class ElectricityService
{
    public function __construct(
        protected ElectricityRepository $repository,
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    /**
     * Vend electricity.
     *
     * @param  ElectricityVendDTO  $dto
     * @param  string|null         $providerName  Override provider
     * @param  Transaction|null    $existingTransaction  If provided, update this instead of creating a new one
     */
    public function vend(ElectricityVendDTO $dto, ?string $providerName = null, ?Transaction $existingTransaction = null)
    {
        // Get the provider (uses default from config if not specified)
        $provider = $this->providerFactory->make($providerName);

        Log::info('Electricity Vending Started', [
            'meter' => $dto->meterNumber,
            'amount' => $dto->amount,
            'provider' => $provider->getName()
        ]);

        // Use existing transaction or create a new one
        if ($existingTransaction) {
            $transaction = $existingTransaction;
        } else {
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
        }

        try {
            // Use the provider to vend electricity
            $result = $provider->vendElectricity($dto);

            // Update Transaction Status based on response
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

            // Append vend result to existing meta
            $meta = $transaction->meta ?? [];
            $meta['vend_response'] = $result;
            $meta['vend_status'] = $transaction->status;

            // Extract the electricity token for easy access
            $meta['electricity_token'] = $result['data']['token']
                ?? $result['token']
                ?? null;

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
            $meta['vend_error'] = $e->getMessage();
            $transaction->meta = $meta;
            $transaction->save();

            throw $e;
        }
    }
}
