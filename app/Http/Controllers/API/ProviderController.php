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
     * Get Data plans (bundles).
     *
     * Usage: GET /v2/data/plans?network=MTN
     */
    public function dataPlans(): JsonResponse
    {
        try {
            $billProvider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            $network = strtoupper(request()->query('network', 'MTN'));

            $billPaymentProvider = $this->providerFactory->make($billProvider);
            $data = $billPaymentProvider->getDataPlans([
                'vertical' => 'DATA',
                'provider' => $network,
            ]);

            return $this->success($data['data'] ?? $data, "Data plans for {$network} fetched successfully.");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get cable TV plans/packages for a given provider (DSTV, GOTV, STARTIMES).
     *
     * Usage:
     *   GET /v1/cable/plans?disco=DSTV
     *   GET /v1/cable/plans?disco=GOTV
     *   GET /v1/cable/plans?disco=STARTIMES
     */
    public function cablePlans(): JsonResponse
    {
        try {
            $billProvider = request()->header('X-BILL-PROVIDER') ?? request()->input('provider') ?? 'buypower';
            $disco = strtoupper(request()->query('disco', 'DSTV'));

            $billPaymentProvider = $this->providerFactory->make($billProvider);

            // DSTV uses the tariff endpoint, GOTV & STARTIMES use the bouquets endpoint
            if ($disco === 'DSTV') {
                $data = $billPaymentProvider->getTariff([
                    'vertical' => 'TV',
                    'provider' => 'DSTV',
                ]);
            } else {
                $data = $billPaymentProvider->getBouquets([
                    'vertical' => 'TV',
                    'provider' => 'DSTV',
                    'type' => $disco,
                ]);
            }

            return $this->success($data['data'] ?? $data, "Cable plans for {$disco} fetched successfully.");
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
