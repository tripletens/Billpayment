<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\TransactionListItemDTO;
use Illuminate\Http\Request;
use App\Models\Transaction;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query();

        // Filter by Type (e.g., 'electricity', 'airtime')
        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        // Filter by Category Group if needed
        if ($request->has('category')) {
            $category = $request->query('category');
            if ($category === 'utilities') {
                $query->whereIn('type', ['electricity', 'water', 'waste']);
            } elseif ($category === 'entertainment') {
                $query->whereIn('type', ['cable_tv', 'internet']);
            } elseif ($category === 'telecoms') {
                $query->whereIn('type', ['airtime', 'data']);
            }
        }

        // Filter by Status
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Date Range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->query('start_date'));
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->query('end_date'));
        }

        $transactions = $query->latest()->paginate(20);

        return $this->success($transactions, 'Transactions retrieved successfully.');
    }

    /**
     * Get transactions with full details including vend response and wallet transactions
     * For v2 API with date range and pagination support
     */
    public function transactions(Request $request)
    {
        try {
            $limit = (int) ($request->query('limit') ?? 50);
            $page = (int) ($request->query('page') ?? 1);
            $start = $request->query('start');
            $end = $request->query('end');

            $query = Transaction::query();

            // Date range filtering
            if ($start) {
                $query->whereDate('created_at', '>=', $start);
            }
            if ($end) {
                $query->whereDate('created_at', '<=', $end);
            }

            $transactions = $query->latest()->paginate($limit, ['*'], 'page', $page);

            // Transform transactions to DTOs
            $transformedTransactions = $transactions->items();
            // In a real scenario, map transaction data to TransactionListItemDTO
            // For now, we'll just pass through the array representation

            return response()->json([
                'status' => 'ok',
                'message' => 'Order fetched successfully!',
                'data' => $transformedTransactions,
                'meta' => [
                    'pages' => $transactions->lastPage(),
                    'total' => $transactions->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
