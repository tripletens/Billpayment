<?php

namespace App\Repositories;

use App\Contracts\TransactionRepositoryInterface;
use App\DTOs\TransactionFilterDTO;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Get paginated transactions filtered by the given criteria.
     */
    public function getFiltered(TransactionFilterDTO $filters): LengthAwarePaginator
    {
        $query = Transaction::query();

        // Scope to a specific user
        if ($filters->userId !== null) {
            $query->where('user_id', $filters->userId);
        }

        // Filter by type
        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }

        // Filter by category group
        if ($filters->category !== null) {
            $typeMap = [
                'utilities'     => ['electricity', 'water', 'waste'],
                'entertainment' => ['cable_tv', 'internet'],
                'telecoms'      => ['airtime', 'data'],
            ];

            if (isset($typeMap[$filters->category])) {
                $query->whereIn('type', $typeMap[$filters->category]);
            }
        }

        // Filter by status
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        // Filter by provider
        if ($filters->provider !== null) {
            $query->where('provider_name', $filters->provider);
        }

        // Filter by reference
        if ($filters->reference !== null) {
            $query->where('reference', $filters->reference);
        }

        // Search across reference and meta fields
        if ($filters->search !== null) {
            $search = $filters->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('meta->bill_data->email', 'like', "%{$search}%")
                  ->orWhere('meta->bill_data->customer_name', 'like', "%{$search}%")
                  ->orWhere('meta->bill_data->phone', 'like', "%{$search}%")
                  ->orWhere('meta->bill_data->meter_number', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($filters->startDate !== null) {
            $query->whereDate('created_at', '>=', $filters->startDate);
        }
        if ($filters->endDate !== null) {
            $query->whereDate('created_at', '<=', $filters->endDate);
        }

        // Eager load user for admin views
        $query->with('user:id,name,email');

        return $query->latest()->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    /**
     * Find a single transaction by reference, optionally scoped to a user.
     */
    public function findByReference(string $reference, ?int $userId = null): ?Transaction
    {
        $query = Transaction::where('reference', $reference);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->first();
    }
}
