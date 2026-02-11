<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EntertainmentService;
use App\Services\TelecomsService;
use App\Traits\ApiResponseTrait;

class BillPaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected EntertainmentService $entertainmentService,
        protected TelecomsService $telecomsService
    ) {}

    public function vendEntertainment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'provider' => 'required|string',
            'type' => 'required|string', // e.g., 'cable_tv', 'internet'
            // Add other fields as necessary
        ]);

        try {
            $transaction = $this->entertainmentService->purchaseSubscription($request->all());
            return $this->success($transaction, 'Entertainment purchase initiated successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function vendTelecoms(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:airtime,data',
            'phone_number' => 'required|string',
            // Add other fields
        ]);

        try {
            $transaction = $this->telecomsService->purchaseAirtimeOrData($request->all());
            return $this->success($transaction, 'Telecoms purchase initiated successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
