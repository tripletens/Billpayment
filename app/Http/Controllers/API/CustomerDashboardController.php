<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerDashboardController extends Controller
{
    /**
     * Get consolidated customer dashboard analytics.
     *
     * GET /api/v1/user/dashboard?user_id=1
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->query('user_id');
        $query = Transaction::where('user_id', $userId);

        return $this->success([
            'financial_overview'   => $this->financialOverview($query->clone()),
            'spending_breakdown'   => $this->spendingBreakdown($query->clone()),
            'monthly_trend'        => $this->monthlyTrend($query->clone()),
            'service_insights'     => $this->serviceInsights($query->clone()),
            'behavioral_metrics'   => $this->behavioralMetrics($query->clone()),
            'recent_transactions'  => $this->recentTransactions($query->clone()),
        ], 'Dashboard analytics retrieved successfully.');
    }

    // ── 1. Financial Overview ────────────────────────────────────────────────

    protected function financialOverview($query): array
    {
        $now = Carbon::now();
        $successStatuses = ['success', 'completed'];

        $allTransactions = $query->clone();
        $successfulAll = $query->clone()->whereIn('status', $successStatuses);
        $successfulMonth = $query->clone()
            ->whereIn('status', $successStatuses)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year);

        return [
            'total_spent_this_month' => (float) $successfulMonth->sum('amount'),
            'total_spent_all_time'   => (float) $successfulAll->sum('amount'),
            'total_transactions'     => $allTransactions->clone()->count(),
            'successful_count'       => $query->clone()->whereIn('status', $successStatuses)->count(),
            'failed_count'           => $query->clone()->where('status', 'failed')->count(),
            'pending_count'          => $query->clone()->whereIn('status', ['pending', 'pending_payment', 'paid'])->count(),
        ];
    }

    // ── 2. Spending Breakdown by Category ────────────────────────────────────

    protected function spendingBreakdown($query): array
    {
        $successStatuses = ['success', 'completed'];

        $categories = $query
            ->whereIn('status', $successStatuses)
            ->select('type', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('type')
            ->orderByDesc('total_amount')
            ->get();

        $grandTotal = $categories->sum('total_amount');

        return $categories->map(function ($cat) use ($grandTotal) {
            return [
                'category'          => $cat->type,
                'total_amount'      => (float) $cat->total_amount,
                'transaction_count' => (int) $cat->transaction_count,
                'percentage'        => $grandTotal > 0
                    ? round(($cat->total_amount / $grandTotal) * 100, 1)
                    : 0,
            ];
        })->values()->toArray();
    }

    // ── 3. Monthly Spending Trend (last 6 months) ────────────────────────────

    protected function monthlyTrend($query): array
    {
        $successStatuses = ['success', 'completed'];
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $months = $query
            ->whereIn('status', $successStatuses)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months with zero
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthKey = Carbon::now()->subMonths($i)->format('Y-m');
            $found = $months->firstWhere('month', $monthKey);
            $result[] = [
                'month' => $monthKey,
                'label' => Carbon::now()->subMonths($i)->format('M Y'),
                'total' => (float) ($found->total ?? 0),
                'count' => (int) ($found->count ?? 0),
            ];
        }

        return $result;
    }

    // ── 4. Service Usage Insights ────────────────────────────────────────────

    protected function serviceInsights($query): array
    {
        $successStatuses = ['success', 'completed'];
        $successful = $query->clone()->whereIn('status', $successStatuses);

        // Most used service
        $mostUsedService = $query->clone()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->first();

        // Most used provider
        $mostUsedProvider = $query->clone()
            ->whereNotNull('provider_name')
            ->select('provider_name', DB::raw('COUNT(*) as count'))
            ->groupBy('provider_name')
            ->orderByDesc('count')
            ->first();

        return [
            'most_used_service'      => $mostUsedService->type ?? null,
            'most_used_provider'     => $mostUsedProvider->provider_name ?? null,
            'avg_transaction_amount' => round((float) $successful->clone()->avg('amount'), 2),
            'highest_transaction'    => (float) ($successful->clone()->max('amount') ?? 0),
            'lowest_transaction'     => (float) ($successful->clone()->min('amount') ?? 0),
        ];
    }

    // ── 5. Behavioral Metrics ────────────────────────────────────────────────

    protected function behavioralMetrics($query): array
    {
        $total = $query->clone()->count();
        $successCount = $query->clone()->whereIn('status', ['success', 'completed'])->count();
        $failedCount = $query->clone()->where('status', 'failed')->count();

        $firstTransaction = $query->clone()->oldest()->first();
        $lastTransaction = $query->clone()->latest()->first();

        // Weeks since first transaction
        $weeksSinceFirst = $firstTransaction
            ? max(Carbon::parse($firstTransaction->created_at)->diffInWeeks(now()), 1)
            : 1;

        $avgPerWeek = round($total / $weeksSinceFirst, 1);

        // Days since last transaction
        $daysSinceLast = $lastTransaction
            ? Carbon::parse($lastTransaction->created_at)->diffInDays(now())
            : null;

        // Frequency score
        $frequencyScore = match (true) {
            $avgPerWeek >= 5  => 'High',
            $avgPerWeek >= 2  => 'Medium',
            default           => 'Low',
        };

        // Most active day of week
        $mostActiveDay = $query->clone()
            ->select(DB::raw("DAYNAME(created_at) as day_name"), DB::raw('COUNT(*) as count'))
            ->groupBy('day_name')
            ->orderByDesc('count')
            ->first();

        // Peak time
        $peakHour = $query->clone()
            ->select(DB::raw("HOUR(created_at) as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        $peakTime = match (true) {
            !$peakHour                    => null,
            $peakHour->hour < 12          => 'Morning',
            $peakHour->hour < 17          => 'Afternoon',
            default                       => 'Night',
        };

        return [
            'avg_transactions_per_week'  => $avgPerWeek,
            'days_since_last_transaction' => $daysSinceLast,
            'frequency_score'            => $frequencyScore,
            'success_rate'               => $total > 0 ? round(($successCount / $total) * 100, 1) : 0,
            'failed_rate'                => $total > 0 ? round(($failedCount / $total) * 100, 1) : 0,
            'most_active_day'            => $mostActiveDay->day_name ?? null,
            'peak_time'                  => $peakTime,
            'peak_hour'                  => $peakHour->hour ?? null,
        ];
    }

    // ── 6. Recent Transactions ───────────────────────────────────────────────

    protected function recentTransactions($query): array
    {
        return $query
            ->latest()
            ->limit(5)
            ->get(['id', 'reference', 'type', 'amount', 'status', 'provider_name', 'meta', 'created_at'])
            ->toArray();
    }
}
