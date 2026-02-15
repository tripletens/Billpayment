<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Factories\BillPaymentProviderFactory;
use Illuminate\Http\JsonResponse;

class ProviderController extends Controller
{
    public function __construct(
        protected BillPaymentProviderFactory $providerFactory
    ) {}

    /**
     * Get provider reliability index
     */
    public function reliabilityIndex(): JsonResponse
    {
        try {
            // Default to buypower as specified by the user
            $provider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            
            $billPaymentProvider = $this->providerFactory->make($provider);
            $data = $billPaymentProvider->getReliabilityIndex();

            return response()->json([
                "status" => "ok",
                "message" => "Successful",
                "data" => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get provider tariffs (price list)
     */
    public function tariffs(): JsonResponse
    {
        try {
            $provider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            
            $billPaymentProvider = $this->providerFactory->make($provider);
            $data = $billPaymentProvider->getTariff(request()->all());

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get TV bouquets (packages)
     */
    public function bouquets(): JsonResponse
    {
        try {
            $provider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            
            $billPaymentProvider = $this->providerFactory->make($provider);
            $data = $billPaymentProvider->getBouquets(request()->all());

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Data plans (bundles)
     */
    public function dataPlans(): JsonResponse
    {
        try {
            $provider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            
            $billPaymentProvider = $this->providerFactory->make($provider);
            $data = $billPaymentProvider->getDataPlans(request()->all());

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}




