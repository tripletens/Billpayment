<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;

class MultiBillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.internal.server_token', 'test_server_token');
        
        // Disable middleware for easier functional testing of the logic
        $this->withoutMiddleware();
    }

    public function test_can_vend_entertainment_subscription()
    {
        $response = $this->postJson('/api/v1/vend/entertainment', [
            'type' => 'cable_tv',
            'provider' => 'vtpass',
            'amount' => 5000,
            'smartcard_number' => '1234567890',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('transactions', [
            'type' => 'cable_tv',
            'amount' => 5000,
            'status' => 'success', // Mocked success
        ]);
        
        $transaction = Transaction::where('type', 'cable_tv')->first();
        $this->assertEquals('vtpass', $transaction->meta['provider']);
    }

    public function test_can_vend_telecoms_airtime()
    {
        $response = $this->postJson('/api/v1/vend/telecoms', [
            'type' => 'airtime',
            'phone_number' => '08012345678',
            'amount' => 500,
            'provider' => 'mtn',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('transactions', [
            'type' => 'airtime',
            'amount' => 500,
        ]);
    }

    public function test_admin_reporting_api_filters_by_category()
    {
        // Seed transactions
        Transaction::create(['reference' => 'ref1', 'type' => 'electricity', 'amount' => 1000, 'status' => 'success']);
        Transaction::create(['reference' => 'ref2', 'type' => 'cable_tv', 'amount' => 5000, 'status' => 'success']);
        Transaction::create(['reference' => 'ref3', 'type' => 'airtime', 'amount' => 100, 'status' => 'success']);

        // Test Utilities Filter (should include electricity)
        $response = $this->getJson('/api/v1/admin/transactions?category=utilities');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $this->assertEquals('electricity', $response->json('data.data.0.type'));

        // Test Entertainment Filter
        $response = $this->getJson('/api/v1/admin/transactions?category=entertainment');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $this->assertEquals('cable_tv', $response->json('data.data.0.type'));

        // Test Telecoms Filter
        $response = $this->getJson('/api/v1/admin/transactions?category=telecoms');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $this->assertEquals('airtime', $response->json('data.data.0.type'));
    }
}
