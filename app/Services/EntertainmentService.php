<?php

namespace App\Services;

use App\Models\Transaction;
use App\Factories\BillPaymentProviderFactory;

class EntertainmentService
{
    public function __construct(
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    /**
     * Purchase an entertainment subscription (cable TV, internet, streaming).
     *
     * @param  array             $data
     * @param  string|null       $provider             Override provider
     * @param  Transaction|null  $existingTransaction  If provided, update this instead of creating a new one
     */
    public function purchaseSubscription(array $data, ?string $provider = null, ?Transaction $existingTransaction = null)
    {
        // Use existing transaction or create a new one
        if ($existingTransaction) {
            $transaction = $existingTransaction;
        } else {
            $transaction = Transaction::create([
                'reference' => uniqid('ref_'),
                'type' => $data['type'] ?? 'cable_tv',
                'amount' => $data['amount'],
                'status' => 'pending',
                'meta' => $data,
                'provider_name' => $provider ?? $data['provider'] ?? 'default',
                'user_id' => $data['user_id'] ?? null,
            ]);
        }

        try {
            // Process Purchase via Provider
            $providerInstance = $this->providerFactory->make($provider ?? $data['provider'] ?? null);
            $result = $providerInstance->vendEntertainment($data);
            
            // Update Transaction
            if (($result['status'] ?? false) === true || ($result['responseCode'] ?? '') === '00' || ($result['responseCode'] ?? 0) == 200) {
                $transaction->status = 'success';
            } else {
                $transaction->status = 'failed';
            }
            
            // Update meta with vend response
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
