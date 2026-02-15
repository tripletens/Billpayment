<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReliabilityIndexTest extends TestCase
{
    /**
     * Test GET /v2/providers/reliability-index
     */
    public function test_reliability_index_endpoint_returns_data()
    {
        $this->withoutMiddleware();

        $mockData = [
            [
                "vertical" => "DATA",
                "disco_code" => "MTN",
                "success_percentage" => 98,
                "pending_percentage" => 0,
                "failure_percentage" => 2,
                "provider_online" => true
            ]
        ];

        // Mock BuyPower API response
        \Illuminate\Support\Facades\Http::fake([
            '*/reliability-index' => \Illuminate\Support\Facades\Http::response([
                'status' => 'ok',
                'message' => 'Successful',
                'data' => $mockData
            ], 200)
        ]);

        $response = $this->getJson('/api/v2/providers/reliability-index');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'vertical',
                    'disco_code',
                    'success_percentage',
                    'pending_percentage',
                    'failure_percentage',
                    'provider_online',
                ]
            ]
        ]);
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('message', 'Successful');
        
        $response->assertJsonFragment($mockData[0]);
    }
}
