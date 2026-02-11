<?php

namespace App\Services;

use App\Models\Transaction;
use App\Factories\BillPaymentProviderFactory;

class TelecomsService
{
    public function __construct(
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    public function purchaseAirtimeOrData(array $data)
    {
        $type = $data['type'] ?? 'airtime'; // airtime or data

        // 1. Create Transaction
        $transaction = Transaction::create([
            'reference' => uniqid('ref_'),
            'type' => $type,
            'amount' => $data['amount'],
            'status' => 'pending',
            'meta' => $data,
            'provider_name' => $data['provider'] ?? 'default',
            'user_id' => $data['user_id'] ?? null,
        ]);

        try {
            // 2. Process via Provider
            // $provider = $this->providerFactory->make($data['provider'] ?? null);
            
            // Mocking success
            $result = ['status' => true, 'message' => ucfirst($type) . ' purchase successful'];

            // 3. Update Transaction
            $transaction->update(['status' => 'success']);
            
            $meta = $transaction->meta ?? [];
            $meta['api_response'] = $result;
            $transaction->update(['meta' => $meta]);

            return $transaction;

        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            $meta = $transaction->meta ?? [];
            $meta['error'] = $e->getMessage();
            $transaction->update(['meta' => $meta]);
            throw $e;
        }
    }
}
