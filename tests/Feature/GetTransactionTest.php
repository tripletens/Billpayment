<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock HTTP responses for BuyPower - matching the nested structure from API
        Http::fake([
            'https://api.buypower.ng/transaction/*' => Http::response([
                'result' => [
                    'status' => true,
                    'data' => [
                        'id' => 7347238,
                        'amountGenerated' => '9302.33',
                        'disco' => 'ABUJA',
                        'debtAmount' => '0.00',
                        'debtRemaining' => '0.00',
                        'orderId' => '135EE81DAAA59E11648532v',
                        'receiptNo' => '7002202010232575554',
                        'tax' => '697.67',
                        'vendTime' => '2021-06-02 14:47:03',
                        'token' => '2755-2924-2525-9465-7190',
                        'totalAmountPaid' => 500,
                        'units' => '382.8',
                        'vendAmount' => '500',
                        'vendRef' => '7X5J4',
                        'responseCode' => 100,
                        'responseMessage' => 'Payment successful.'
                    ]
                ]
            ], 200),
        ]);
    }

    public function test_get_transaction_endpoint_returns_transaction_details(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/transaction/135EE81DAAA59E11648532v');

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
        // Check structure exists without asserting specific values yet
        $response->assertJsonStructure([
            'status',
            'code',
            'message',
            'data' => [
                'id',
                'amountGenerated',
                'disco',
                'orderId',
                'token'
            ]
        ]);
    }

    public function test_get_transaction_with_different_order_id(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/transaction/ANOTHER_ORDER_ID');

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
}
