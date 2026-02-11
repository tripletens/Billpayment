<?php

namespace App\Repositories;

use App\Models\Transaction;

class ElectricityRepository
{
    public function storeTransaction(array $data)
    {
        return Transaction::create([
            'reference' => $data['reference'] ?? uniqid('ref_'),
            'type' => 'electricity',
            'amount' => $data['amount'] ?? 0,
            'status' => $data['status'] ?? 'pending',
            'meta' => $data['meta'] ?? $data,
            'provider_name' => $data['provider_name'] ?? ($data['meta']['provider'] ?? null),
            'user_id' => $data['user_id'] ?? null,
        ]);
    }
}
