<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\EntertainmentService;
use App\Services\TelecomsService;
use App\Services\ElectricityService;
use App\DTOs\ElectricityVendDTO;
use App\Http\Requests\ElectricityVendRequest;
use App\Http\Requests\EntertainmentVendRequest;
use App\Http\Requests\TelecomsVendRequest;
use App\Http\Requests\MeterCheckRequest;
use App\Factories\BillPaymentProviderFactory;

class BillPaymentController extends Controller
{
    public function __construct(
        protected EntertainmentService $entertainmentService,
        protected TelecomsService $telecomsService,
        protected ElectricityService $electricityService,
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    public function vendElectricity(ElectricityVendRequest $request)
    {
        $dto = new ElectricityVendDTO(
            $request->meter_number,
            $request->disco,
            $request->amount,
            $request->customer_name,
            $request->phone,
            $request->email
        );

        // Allow provider override from request
        $provider = $request->header('X-BILL-PROVIDER') ?? $request->input('provider');

        $transaction = $this->electricityService->vend($dto, $provider);
        return $this->success($transaction, 'Electricity vend initiated successfully.');
    }

    public function vendEntertainment(EntertainmentVendRequest $request)
    {
        // Validation already handled by EntertainmentVendRequest
        // No need for duplicate validation here
        
        try {
            // Allow provider override from request
            $provider = $request->header('X-BILL-PROVIDER') ?? $request->input('provider');
            
            $transaction = $this->entertainmentService->purchaseSubscription(
                $request->validated(), 
                $provider
            );
            
            return $this->success($transaction, 'Entertainment purchase initiated successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function vendTelecoms(TelecomsVendRequest $request)
    {
        // Validation already handled by TelecomsVendRequest
        
        try {
            // Allow provider override from request
            $provider = $request->header('X-BILL-PROVIDER') ?? $request->input('provider');
            
            $transaction = $this->telecomsService->purchaseAirtimeOrData(
                $request->validated(),
                $provider
            );
            
            return $this->success($transaction, 'Telecoms purchase initiated successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function checkMeter(MeterCheckRequest $request)
    {
        try {
            // Allow provider override from request
            $provider = $request->header('X-BILL-PROVIDER') ?? $request->input('provider') ?? 'buypower';
            
            // Get the provider instance
            $billPaymentProvider = $this->providerFactory->make($provider);
            
            // Call the meter check method
            $result = $billPaymentProvider->checkMeter(
                $request->input('meter'),
                $request->input('disco'),
                $request->input('vendType')
            );
            
            // Convert DTO to array for response
            $resultArray = (array) $result;
            
            return $this->success($resultArray, 'Meter check completed successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getTransaction($orderId)
    {
        try {
            // Allow provider override from request
            $provider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            
            // Get the provider instance
            $billPaymentProvider = $this->providerFactory->make($provider);
            
            // Call the get transaction method
            $result = $billPaymentProvider->getTransaction($orderId);
            
            // Convert DTO to array for response
            $resultArray = (array) $result;
            
            return $this->success($resultArray, 'Transaction details retrieved successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}