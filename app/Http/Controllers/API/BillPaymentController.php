<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\EntertainmentService;
use App\Services\TelecomsService;
use App\Services\ElectricityService;
use App\DTOs\ElectricityVendDTO;
use App\Http\Requests\ElectricityVendRequest;

class BillPaymentController extends Controller
{
    public function __construct(
        protected EntertainmentService $entertainmentService,
        protected TelecomsService $telecomsService,
        protected ElectricityService $electricityService
    ) {}

    public function vendElectricity(ElectricityVendRequest $request)
    {
        $dto = new ElectricityVendDTO(
            $request->meter_number,
            $request->disco,
            $request->amount,
            $request->customer_name,
            $request->phone
        );

        // Allow provider override from request
        $provider = $request->header('X-BILL-PROVIDER') ?? $request->input('provider');

        $transaction = $this->electricityService->vend($dto, $provider);
        return $this->success($transaction, 'Electricity vend initiated successfully.');
    }

    public function vendEntertainment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'provider' => 'nullable|string',
            'type' => 'required|string', // e.g., 'cable_tv', 'internet'
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
            'provider' => 'nullable|string',
        ]);

        try {
            $transaction = $this->telecomsService->purchaseAirtimeOrData($request->all());
            return $this->success($transaction, 'Telecoms purchase initiated successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
