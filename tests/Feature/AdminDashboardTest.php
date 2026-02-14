<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test wallet balance endpoint for admin dashboard
     */
    public function test_wallet_balance_endpoint_returns_balance_data()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v2/wallet/balance');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'balance',
                'currency',
                'lastUpdated',
            ],
            'message',
        ]);
        $response->assertJsonPath('data.currency', 'NGN');
    }

    /**
     * Test discos status endpoint returns all discos
     */
    public function test_discos_status_endpoint_returns_all_discos()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v2/discos/status');

        $response->assertStatus(200);
        $response->assertJsonPath('data.ABUJA', true);
        $response->assertJsonPath('data.EKO', true);
        $response->assertJsonPath('data.PROTOGY', false);
        $response->assertJsonCount(13, 'data');
    }

    /**
     * Test transactions endpoint returns paginated results
     */
    public function test_transactions_endpoint_returns_paginated_transactions()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v2/transactions?limit=50&page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data',
            'meta' => [
                'pages',
                'total',
            ],
        ]);
        $response->assertJsonPath('status', 'ok');
    }

    /**
     * Test transactions endpoint with date range filtering
     */
    public function test_transactions_endpoint_filters_by_date_range()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v2/transactions?start=2021-09-01&end=2021-09-30&limit=50&page=1');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'ok');
    }

    /**
     * Test transactions endpoint respects limit parameter
     */
    public function test_transactions_endpoint_respects_limit_parameter()
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v2/transactions?limit=10&page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data', 'meta']);
    }
}
