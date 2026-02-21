<?php

namespace App\Http\Controllers\API;

use App\DTOs\TransactionFilterDTO;
use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * List all transactions with basic filtering (v1 legacy).
     */
    public function index(Request $request)
    {
        $filters = TransactionFilterDTO::fromRequest($request);

        $transactions = $this->transactionService->list($filters);

        return $this->success($transactions, 'Transactions retrieved successfully.');
    }

    /**
     * Get transactions with full details, search, and advanced filtering.
     * For v2 API with date range and pagination support.
     */
    public function transactions(Request $request)
    {
        try {
            $filters = TransactionFilterDTO::fromRequest($request);

            $transactions = $this->transactionService->list($filters);

            return response()->json([
                'status' => 'success',
                'message' => 'Transactions fetched successfully!',
                'data' => $transactions->items(),
                'meta' => [
                    'pages' => $transactions->lastPage(),
                    'total' => $transactions->total(),
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
