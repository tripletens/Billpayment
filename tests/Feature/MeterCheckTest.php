<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeterCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock HTTP responses for BuyPower
        Http::fake([
            'https://api.buypower.ng/check/meter' => Http::response([
                'error' => false,
                'discoCode' => 'ABUJA',
                'vendType' => 'PREPAID',
                'meterNo' => '12345678',
                'minVendAmount' => 500,
                'maxVendAmount' => 30000,
                'responseCode' => 100,
                'outstanding' => 0,
                'debtRepayment' => 0.15,
                'name' => 'Test User',
                'address' => 'Test Address',
                'tariff' => 'Test Tariff',
                'tariffClass' => 'Test Tariff Class'
            ], 200),
        ]);
    }

    public function test_meter_check_endpoint_validates_required_parameters(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/check/meter');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['meter', 'disco', 'vendType']);
    }

    public function test_meter_check_endpoint_validates_disco_enum(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/check/meter?meter=12345678&disco=INVALID_DISCO&vendType=PREPAID');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['disco']);
    }

    public function test_meter_check_endpoint_validates_vend_type_enum(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/check/meter?meter=12345678&disco=AEDC&vendType=INVALID_TYPE');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vendType']);
    }

    public function test_meter_check_endpoint_accepts_valid_parameters(): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson('/api/v1/check/meter?meter=12345678&disco=AEDC&vendType=PREPAID');

        // Should return 200 (even if provider fails, we test the validation)
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    /**
     * Test with all valid DISCO options
     */
    public function test_meter_check_accepts_all_valid_discos(): void
    {
        $this->withoutMiddleware();

        $validDiscos = ['AEDC', 'EKEDC', 'IKEDC', 'IBEDC', 'JEDC', 'KEDCO', 'KAEDCO', 'PHED', 'EEDC', 'BEDC'];

        foreach ($validDiscos as $disco) {
            $response = $this->getJson("/api/v1/check/meter?meter=12345678&disco={$disco}&vendType=PREPAID");

            $response->assertStatus(200);
        }
    }
}

