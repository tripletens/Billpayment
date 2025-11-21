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
            'meta' => json_encode($data),
        ]);
    }
}
