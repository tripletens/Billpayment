<?php

namespace App\Http\Controllers\API;

use App\DTOs\TransactionFilterDTO;
use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * List transactions filtered by criteria.
     *
     * The user is identified via the `user_id` query param,
     * passed by the calling backend (authenticated via server token).
     */
    public function index(Request $request)
    {
        $filters = TransactionFilterDTO::fromRequest($request);

        $transactions = $this->transactionService->list($filters);

        return $this->success($transactions, 'Transactions retrieved successfully.');
    }

    /**
     * Get a single transaction by reference.
     */
    public function show(Request $request, string $reference)
    {
        try {
            $userId = $request->has('user_id') ? (int) $request->input('user_id') : null;

            $transaction = $this->transactionService->getByReference($reference, $userId);

            return $this->success($transaction, 'Transaction retrieved successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Transaction not found.', 404);
        }
    }
}
