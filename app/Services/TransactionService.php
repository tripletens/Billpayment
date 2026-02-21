<?php

namespace App\Services;

use App\Contracts\TransactionRepositoryInterface;
use App\DTOs\TransactionFilterDTO;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * List transactions filtered by the given criteria.
     */
    public function list(TransactionFilterDTO $filters): LengthAwarePaginator
    {
        return $this->transactionRepository->getFiltered($filters);
    }

    /**
     * Get a single transaction by reference.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getByReference(string $reference, ?int $userId = null): Transaction
    {
        $transaction = $this->transactionRepository->findByReference($reference, $userId);

        if (! $transaction) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                'Transaction not found.'
            );
        }

        return $transaction;
    }
}
