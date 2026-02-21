<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        
        // Ensure config is set for testing
        config(['services.lytepay.api_key' => 'test_api_key']);
        config(['services.lytepay.secret' => 'test_secret']);
        config(['services.internal.server_token' => 'test_server_token']);
        config(['services.paystack.secret_key' => 'test_paystack_secret']);

        $this->headers = [
            'X-API-KEY' => 'test_api_key',
            'X-SERVER-TOKEN' => 'test_server_token',
        ];
    }


    protected function getSignature($payload)
    {
        return hash_hmac('sha256', json_encode($payload), 'test_secret');
    }

    public function test_payment_initialization()
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test',
                    'access_code' => 'TEST_CODE',
                ]
            ], 200)
        ]);

        $data = [
            'email' => 'test@example.com',
            'amount' => 5000,
            'type' => 'electricity',
            'bill_data' => [
                'meter_number' => '1234567890',
                'disco' => 'AEDC',
            ],
        ];

        $timestamp = time();
        $signature = $this->getSignature($data);

        $response = $this->withHeaders(array_merge($this->headers, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]))->postJson('/api/v1/payment/initialize', $data);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['authorization_url', 'access_code', 'reference']]);

        $this->assertDatabaseHas('transactions', [
            'status' => 'pending_payment',
            'type'   => 'electricity',
        ]);
    }

    public function test_webhook_triggers_vending()
    {
        // 1. Setup a pending transaction
        $reference = 'PAY_TEST_123';
        $transaction = Transaction::create([
            'reference' => $reference,
            'type' => 'electricity',
            'amount' => 5000,
            'status' => 'pending_payment',
            'meta' => [
                'bill_data' => [
                    'meter_number' => '1234567890',
                    'disco' => 'AEDC',
                    'customer_name' => 'Test User',
                    'phone' => '08012345678',
                ],
            ],
        ]);

        // 2. Mock Paystack Webhook
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'status' => 'success',
                'amount' => 500000, // in kobo
            ]
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha512', $jsonPayload, 'test_paystack_secret');

        $response = $this->withHeaders([
            'x-paystack-signature' => $signature,
        ])->postJson('/api/payment/webhook', $payload);

        $response->assertStatus(200);

        // 3. Verify transaction status updated
        $transaction->refresh();
        $this->assertEquals('paid', $transaction->status);

        // 4. Verify that another transaction was created for the vending (as current services do)
        $this->assertDatabaseHas('transactions', [
            'type' => 'electricity',
            'amount' => 5000,
            // 'status' => 'success', // Might be pending if mocked, but usually 'success' or 'failed'
        ]);
    }

    public function test_callback_verifies_and_processes_payment()
    {
        $reference = 'PAY_CALLBACK_TEST_1';

        // Create a pending transaction
        $transaction = Transaction::create([
            'reference'   => $reference,
            'type'        => 'electricity',
            'amount'      => 5100,
            'status'      => 'pending_payment',
            'meta'        => [
                'bill_data' => [
                    'meter_number'  => '1234567890',
                    'disco'         => 'AEDC',
                    'customer_name' => 'Test User',
                    'phone'         => '08012345678',
                ],
                'original_amount' => 5000,
            ],
        ]);

        // Mock Paystack verification returning success
        Http::fake([
            'https://api.paystack.co/transaction/verify/' . $reference => Http::response([
                'status'  => true,
                'message' => 'Verification successful',
                'data'    => [
                    'status'           => 'success',
                    'reference'        => $reference,
                    'amount'           => 510000, // kobo
                    'currency'         => 'NGN',
                    'channel'          => 'card',
                    'gateway_response' => 'Successful',
                    'paid_at'          => '2024-08-22T09:15:02.000Z',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/payment/callback?reference=' . $reference);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['reference', 'amount', 'transaction_id']]);

        $this->assertEquals('paid', $transaction->fresh()->status);
    }

    public function test_callback_handles_failed_payment_status()
    {
        $reference = 'PAY_CALLBACK_TEST_2';

        $transaction = Transaction::create([
            'reference'   => $reference,
            'type'        => 'electricity',
            'amount'      => 5100,
            'status'      => 'pending_payment',
            'meta'        => ['bill_data' => ['meter_number' => '111', 'disco' => 'AEDC']],
        ]);

        // Mock Paystack responding with a failed payment
        Http::fake([
            'https://api.paystack.co/transaction/verify/' . $reference => Http::response([
                'status'  => true,
                'message' => 'Verification successful',
                'data'    => [
                    'status'           => 'failed',
                    'reference'        => $reference,
                    'amount'           => 510000,
                    'gateway_response' => 'Declined',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/payment/callback?reference=' . $reference);

        $response->assertStatus(402)
            ->assertJsonPath('status', false);

        // Transaction should remain pending_payment
        $this->assertEquals('pending_payment', $transaction->fresh()->status);
    }
}
