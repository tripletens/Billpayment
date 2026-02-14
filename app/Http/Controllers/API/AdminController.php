<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\WalletBalanceDTO;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * Get wallet balance (admin dashboard only)
     */
    public function walletBalance(): JsonResponse
    {
        try {
            // Default mock wallet balance
            // In a real implementation, this would query the wallet service/database
            $balanceData = [
                'balance' => '3294295.00',
                'currency' => 'NGN',
                'last_updated' => now()->toIso8601String(),
            ];

            $wallet = WalletBalanceDTO::fromArray($balanceData);
            $walletArray = (array) $wallet;

            return $this->success($walletArray, 'Wallet balance retrieved successfully.');
        } catch (\Exception $e) {
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
}
