<?php

namespace App\Http\Controllers\API;

use App\DTOs\WalletBalanceDTO;
use App\Factories\BillPaymentProviderFactory;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(
        protected BillPaymentProviderFactory $providerFactory,
        protected EmailService $emailService
    ) {}

    /**
     * Get BuyPower wallet balance and send email notification.
     */
    public function walletBalance(): JsonResponse
    {
        try {
            $provider = $this->providerFactory->make('buypower');
            $response = $provider->getWalletBalance();

            $balance = $response['data']['balance'] ?? $response['balance'] ?? '0.00';
            $currency = $response['data']['currency'] ?? 'NGN';
            $threshold = (float) Setting::get('wallet_min_balance', 20000);

            $balanceData = [
                'balance' => $balance,
                'currency' => $currency,
                'last_updated' => now()->toIso8601String(),
                'threshold' => $threshold,
                'low_balance_alert' => (float) $balance < $threshold,
            ];

            $wallet = WalletBalanceDTO::fromArray($balanceData);

            // Only send alert email when balance is below threshold
            if ((float) $balance < $threshold) {
                $this->sendLowBalanceAlert($balance, $currency, $threshold);
            }

            return $this->success((array) $wallet, 'Wallet balance retrieved successfully.');
        } catch (\Exception $e) {
            Log::error('Wallet balance fetch failed', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Send low balance alert email to admin team.
     */
    protected function sendLowBalanceAlert(string $balance, string $currency, float $threshold): void
    {
        try {
            $formattedBalance = $currency . ' ' . number_format((float) $balance, 2);
            $formattedThreshold = $currency . ' ' . number_format($threshold, 2);
            $timestamp = now()->format('d M Y, H:i') . ' WAT';

            $html = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #fff; padding: 30px; border-radius: 12px 12px 0 0;'>
                        <h1 style='margin:0; font-size: 20px;'>⚠️ Low Wallet Balance Alert</h1>
                    </div>
                    <div style='background: #fef2f2; padding: 30px;'>
                        <div style='background: #fff; border: 2px solid #fca5a5; border-radius: 12px; padding: 25px; text-align: center; margin-bottom: 20px;'>
                            <p style='color: #dc2626; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; margin: 0 0 8px;'>Current Balance</p>
                            <p style='font-size: 36px; font-weight: 700; color: #dc2626; margin: 0;'>{$formattedBalance}</p>
                            <p style='color: #94a3b8; font-size: 13px; margin: 10px 0 0;'>Minimum threshold: {$formattedThreshold}</p>
                        </div>
                        <p style='color: #991b1b; font-size: 14px; text-align: center; font-weight: 600;'>Your BuyPower wallet balance has dropped below the minimum threshold. Please top up immediately to avoid service interruption.</p>
                        <p style='color: #64748b; font-size: 12px; text-align: center; margin-top: 15px;'>Checked at: {$timestamp}</p>
                    </div>
                    <div style='background: #1a1a2e; color: rgba(255,255,255,0.5); padding: 15px; border-radius: 0 0 12px 12px; text-align: center; font-size: 11px;'>
                        <p>BillPay Admin — Low Balance Alert</p>
                    </div>
                </div>
            ";

            $this->emailService->sendRaw([
                'email' => 'info@lythubtechnologies.com',
                'name' => 'LythubTech Admin',
                'subject' => "⚠️ LOW BALANCE ALERT: {$formattedBalance} (below {$formattedThreshold})",
                'text' => "BuyPower wallet balance is {$formattedBalance}, below the minimum threshold of {$formattedThreshold}. Please top up immediately.",
                'html' => $html,
                'category' => 'LowBalanceAlert',
                'from' => [
                    'email' => 'noreply@lythubtechnologies.com',
                    'name' => 'BillPay System',
                ],
                'cc' => [
                    ['email' => 'iamkaluchinonso@gmail.com', 'name' => 'Chinonso'],
                    ['email' => 'chinonso@lythubtechnologies.com', 'name' => 'Chinonso Lythub'],
                ],
            ]);

            Log::warning('Low wallet balance alert sent', [
                'balance' => $formattedBalance,
                'threshold' => $formattedThreshold,
            ]);
        } catch (\Exception $e) {
            Log::warning('Low balance alert email failed (non-fatal)', ['error' => $e->getMessage()]);
        }
    }
    /**
     * Get BuyPower transaction history.
     *
     * Query params: start, end, limit (max 50), page
     */
    public function providerTransactions(): JsonResponse
    {
        try {
            $provider = $this->providerFactory->make('buypower');

            $params = array_filter([
                'start' => request()->query('start'),
                'end'   => request()->query('end'),
                'limit' => min((int) request()->query('limit', 20), 50),
                'page'  => (int) request()->query('page', 1),
            ]);

            $result = $provider->getTransactionHistory($params);

            return $this->success(
                $result['data'] ?? $result,
                'BuyPower transactions fetched successfully.'
            );
        } catch (\Exception $e) {
            Log::error('Provider transactions fetch failed', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get disco status (admin dashboard and customer portal)
     */
    public function discosStatus(): JsonResponse
    {
        try {
            // All available discos with their status
            $discosStatus = [
                'ABUJA' => true,
                'EKO' => true,
                'IKEJA' => true,
                'IBADAN' => true,
                'ENUGU' => true,
                'PH' => true,
                'JOS' => true,
                'KADUNA' => true,
                'KANO' => true,
                'BH' => true,
                'PROTOGY' => false,
                'PHISBOND' => false,
                'ACCESSPOWER' => false,
            ];

            return $this->success($discosStatus, 'Discos status retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get the current active bill payment provider.
     */
    public function getProvider(BillPaymentProviderFactory $factory): JsonResponse
    {
        return $this->success([
            'active_provider' => Setting::get('bill_payment_provider', config('billpayment.provider', 'buypower')),
            'available_providers' => $factory->availableProviders(),
        ], 'Active provider retrieved successfully.');
    }

    /**
     * Update the active bill payment provider.
     */
    public function updateProvider(Request $request, BillPaymentProviderFactory $factory): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:'.implode(',', $factory->availableProviders()),
        ]);

        Setting::set('bill_payment_provider', $request->provider);

        return $this->success([
            'active_provider' => $request->provider,
        ], 'Bill payment provider updated to '.$request->provider);
    }
}
