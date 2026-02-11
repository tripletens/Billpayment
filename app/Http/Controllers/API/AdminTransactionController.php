<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
}
