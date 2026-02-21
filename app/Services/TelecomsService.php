<?php

namespace App\Services;

use App\Models\Transaction;
use App\Factories\BillPaymentProviderFactory;

class TelecomsService
{
    public function __construct(
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    /**
     * Purchase airtime or data.
     *
     * @param  array             $data
     * @param  string|null       $provider         Override provider
     * @param  Transaction|null  $existingTransaction  If provided, update this instead of creating a new one
     */
    public function purchaseAirtimeOrData(array $data, ?string $provider = null, ?Transaction $existingTransaction = null)
    {
        $type = $data['type'] ?? 'airtime'; // airtime or data

        // Use existing transaction or create a new one
        if ($existingTransaction) {
            $transaction = $existingTransaction;
        } else {
            $transaction = Transaction::create([
                'reference' => uniqid('ref_'),
                'type' => $type,
                'amount' => $data['amount'],
                'status' => 'pending',
                'meta' => $data,
                'provider_name' => $provider ?? $data['provider'] ?? 'default',
                'user_id' => $data['user_id'] ?? null,
            ]);
        }

        try {
            // Process via Provider
            $providerInstance = $this->providerFactory->make($provider ?? $data['provider'] ?? null);
            $result = $providerInstance->vendTelecoms($data);
            
            // Update Transaction
            if (($result['status'] ?? false) === true || ($result['responseCode'] ?? '') === '00' || ($result['responseCode'] ?? 0) == 200) {
                $transaction->status = 'success';
            } else {
                $transaction->status = 'failed';
            }
            
            $meta = $transaction->meta ?? [];
            $meta['vend_response'] = $result;
            $meta['vend_status'] = $transaction->status;
            $transaction->meta = $meta;
            $transaction->save();

            return $transaction;

        } catch (\Exception $e) {
            $transaction->status = 'failed';
            $meta = $transaction->meta ?? [];
            $meta['vend_error'] = $e->getMessage();
            $transaction->meta = $meta;
            $transaction->save();
            throw $e;
        }
    }
}
