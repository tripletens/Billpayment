<?php

namespace App\Services;

use App\Models\Transaction;
use App\Factories\BillPaymentProviderFactory;

class EntertainmentService
{
    public function __construct(
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    public function purchaseSubscription(array $data)
    {
        // 1. Create Transaction (Pending)
        $transaction = Transaction::create([
            'reference' => uniqid('ref_'),
            'type' => 'entertainment', // or specific like 'cable_tv'
            'amount' => $data['amount'],
            'status' => 'pending',
            'meta' => $data,
            'provider_name' => $data['provider'] ?? 'default',
            'user_id' => $data['user_id'] ?? null,
        ]);

        try {
            // 2. Process Purchase via Provider
            $provider = $this->providerFactory->make($data['provider'] ?? null);
            // $result = $provider->purchaseSubscription($data); // Method to be implemented in provider
            
            // Mocking success for now
            $result = ['status' => true, 'message' => 'Subscription successful'];

            // 3. Update Transaction
            if ($result['status']) {
                $transaction->update(['status' => 'success']);
            } else {
                $transaction->update(['status' => 'failed']);
            }
            
            // Update meta with response
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
