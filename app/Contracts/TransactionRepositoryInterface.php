<?php

namespace App\Contracts;

use App\DTOs\TransactionFilterDTO;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get paginated transactions filtered by the given criteria.
     */
    public function getFiltered(TransactionFilterDTO $filters): LengthAwarePaginator;

    /**
     * Find a single transaction by its reference.
     *
     * @param  string   $reference
     * @param  int|null $userId  Optionally scope to a specific user
     * @return Transaction|null
     */
    public function findByReference(string $reference, ?int $userId = null): ?Transaction;
}
