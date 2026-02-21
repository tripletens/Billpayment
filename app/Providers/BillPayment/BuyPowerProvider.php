<?php

namespace App\Providers\BillPayment;

use App\Contracts\BillPaymentProviderInterface;
use App\DTOs\ElectricityVendDTO;
use App\DTOs\MeterCheckResponseDTO;
use App\DTOs\TransactionResponseDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BuyPowerProvider implements BillPaymentProviderInterface
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('billpayment.buypower.base_url');
        $this->token = config('billpayment.buypower.token');
    }

    public function vendElectricity(ElectricityVendDTO $dto): array
    {
        $payload = [
            'orderId' => \Illuminate\Support\Str::uuid()->toString(),
            'meter' => $dto->meterNumber,
            'disco' => $this->mapDisco($dto->disco),
            'phone' => $dto->phone,
            'paymentType' => 'B2B',
            'vendType' => 'PREPAID',
            'vertical' => 'ELECTRICITY',
            'amount' => (string) $dto->amount,
            'email' => $dto->email ?? 'support@lytbills.com',
            'name' => $dto->customerName,
        ];

        // dd($payload);

        Log::info('BuyPower API Request', [
            'url' => $this->baseUrl . '/vend',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->baseUrl . '/vend', $payload);

        $result = $response->json() ?? [];

        Log::info('BuyPower API Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return $result;
    }

    public function checkMeter(string $meter, string $disco, string $vendType): MeterCheckResponseDTO
    {
        $payload = [
            'meter' => $meter,
            'disco' => $this->mapDisco($disco),
            'vendType' => strtoupper($vendType),
        ];

        Log::info('BuyPower Meter Check Request', [
            'url' => $this->baseUrl . '/check/meter',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get($this->baseUrl . '/check/meter', $payload);

        $result = $response->json() ?? [];

        Log::info('BuyPower Meter Check Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return MeterCheckResponseDTO::fromArray($result);
    }

    public function getTransaction(string $orderId): TransactionResponseDTO
    {
        Log::info('BuyPower Get Transaction Request', [
            'url' => $this->baseUrl . '/transaction/' . $orderId,
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get($this->baseUrl . '/transaction/' . $orderId);

        $result = $response->json() ?? [];

        Log::info('BuyPower Get Transaction Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        // Handle nested response structure: result.data
        $transactionData = $result['data'] ?? $result['result']['data'] ?? $result;

        return TransactionResponseDTO::fromArray($transactionData);
    }

    protected function mapDisco(string $disco): string
    {
        $mapping = [
            'AEDC' => 'ABUJA',
            'EKEDC' => 'EKO',
            'IKEDC' => 'IKEJA',
            'IBEDC' => 'IBADAN',
            'JEDC' => 'JOS',
            'KEDCO' => 'KANO',
            'KAEDCO' => 'KADUNA',
            'PHED' => 'PORTHARCOURT',
            'EEDC' => 'ENUGU',
            'BEDC' => 'BENIN',
        ];

        return $mapping[strtoupper($disco)] ?? strtoupper($disco);
    }

    public function getName(): string
    {
        return 'buypower';
    }

    /**
     * Get BuyPower wallet balance.
     */
    public function getWalletBalance(): array
    {
        Log::info('BuyPower Wallet Balance Request', [
            'url' => $this->baseUrl . '/wallet/balance',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->get($this->baseUrl . '/wallet/balance');

            $result = $response->json() ?? [];

            Log::info('BuyPower Wallet Balance Response', [
                'status' => $response->status(),
                'body' => $result
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('BuyPower Wallet Balance Error', [
                'message' => $e->getMessage()
            ]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get BuyPower transaction history.
     */
    public function getTransactionHistory(array $params = []): array
    {
        Log::info('BuyPower Transaction History Request', [
            'url' => $this->baseUrl . '/transactions',
            'params' => $params,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->get($this->baseUrl . '/transactions', $params);

            $result = $response->json() ?? [];

            Log::info('BuyPower Transaction History Response', [
                'status' => $response->status(),
                'count' => count($result['data'] ?? []),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('BuyPower Transaction History Error', [
                'message' => $e->getMessage()
            ]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    /**
     * Map network names to BuyPower disco codes for VTU.
     */
    protected function mapNetwork(string $network): string
    {
        $mapping = [
            'MTN'      => 'MTN',
            'GLO'      => 'GLO',
            'AIRTEL'   => 'AIRTEL',
            '9MOBILE'  => '9MOBILE',
            'ETISALAT' => '9MOBILE',
        ];

        return $mapping[strtoupper($network)] ?? strtoupper($network);
    }

    public function getReliabilityIndex(): array
    {
        Log::info('BuyPower Reliability Index Request', [
            'url' => $this->baseUrl . '/reliability-index',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->get($this->baseUrl . '/reliability-index');

            $result = $response->json() ?? [];

            Log::info('BuyPower Reliability Index Response', [
                'status' => $response->status(),
                'body' => $result
            ]);

            return $result['data'] ?? $result;
        } catch (\Exception $e) {
            Log::error('BuyPower Reliability Index Error', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getTariff(array $params): array
    {
        Log::info('BuyPower Get Tariff Request', [
            'url' => $this->baseUrl . '/tariff',
            'params' => $params
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->get($this->baseUrl . '/tariff', $params);

            $result = $response->json() ?? [];

            Log::info('BuyPower Get Tariff Response', [
                'status' => $response->status(),
                'body' => $result
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('BuyPower Get Tariff Error', [
                'message' => $e->getMessage()
            ]);
            return ['status' => false, 'message' => 'Failed to fetch tariffs'];
        }
    }

    public function getBouquets(array $params): array
    {
        Log::info('BuyPower Get Bouquets Request', [
            'url' => $this->baseUrl . '/tv/bouquets',
            'params' => $params
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->get($this->baseUrl . '/tv/bouquets', $params);

            $result = $response->json() ?? [];

            Log::info('BuyPower Get Bouquets Response', [
                'status' => $response->status(),
                'body' => $result
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('BuyPower Get Bouquets Error', [
                'message' => $e->getMessage()
            ]);
            return ['status' => false, 'message' => 'Failed to fetch bouquets'];
        }
    }

    public function getDataPlans(array $params): array
    {
        // Ensure vertical is set to DATA as per BuyPower requirements
        $params['vertical'] = 'DATA';

        Log::info('BuyPower Get Data Plans Request', [
            'url' => $this->baseUrl . '/tariff',
            'params' => $params
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->get($this->baseUrl . '/tariff', $params);

            $result = $response->json() ?? [];

            Log::info('BuyPower Get Data Plans Response', [
                'status' => $response->status(),
                'body' => $result
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('BuyPower Get Data Plans Error', [
                'message' => $e->getMessage()
            ]);
            return ['status' => false, 'message' => 'Failed to fetch data plans'];
        }
    }

    public function vendTelecoms(array $data): array
    {
        $network = strtoupper($data['network'] ?? $data['provider'] ?? '');
        $type = strtolower($data['type'] ?? 'airtime');
        $isData = $type === 'data';

        $payload = [
            'orderId' => \Illuminate\Support\Str::uuid()->toString(),
            'meter' => $data['phone_number'] ?? $data['phone'],
            'disco' => $this->mapNetwork($network),
            'phone' => $data['phone_number'] ?? $data['phone'],
            'paymentType' => 'B2B',
            'vendType' => 'PREPAID',
            'vertical' => $isData ? 'DATA' : 'VTU',
            'amount' => (string) $data['amount'],
            'email' => $data['email'] ?? 'support@lytbills.com',
            'name' => $data['customer_name'] ?? 'Customer',
        ];

        // Add tariffClass for data plans
        if ($isData && !empty($data['tariff_class'] ?? $data['tariffClass'] ?? $data['data_plan'])) {
            $payload['tariffClass'] = $data['tariff_class'] ?? $data['tariffClass'] ?? $data['data_plan'];
        }

        Log::info('BuyPower Telecoms Vend Request', [
            'url' => $this->baseUrl . '/vend',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->baseUrl . '/vend', $payload);

        $result = $response->json() ?? [];

        Log::info('BuyPower Telecoms Vend Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return $result;
    }

    public function vendEntertainment(array $data): array
    {
        $payload = [
            'orderId' => \Illuminate\Support\Str::uuid()->toString(),
            'meter' => $data['smartcard_number'] ?? $data['smartCardNumber'] ?? $data['meter'],
            'disco' => strtoupper($data['disco'] ?? $data['provider'] ?? 'DSTV'),
            'phone' => $data['phone'] ?? $data['phone_number'] ?? '',
            'paymentType' => 'B2B',
            'vendType' => 'PREPAID',
            'vertical' => 'TV',
            'amount' => (string) $data['amount'],
            'email' => $data['email'] ?? 'support@lytbills.com',
            'name' => $data['customer_name'] ?? $data['name'] ?? 'Customer',
        ];

        Log::info('BuyPower Entertainment Vend Request', [
            'url' => $this->baseUrl . '/vend',
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->baseUrl . '/vend', $payload);

        $result = $response->json() ?? [];

        Log::info('BuyPower Entertainment Vend Response', [
            'status' => $response->status(),
            'body' => $result
        ]);

        return $result;
    }
}




