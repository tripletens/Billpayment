<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendEntertainmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_vend_entertainment_creates_transaction(): void
    {
        $this->withoutMiddleware();

        $payload = [
            'amount' => 100,
            'type' => 'cable_tv',
            'smartcard_number' => '1234567890',
            'package_code' => 'basic',
            'customer_name' => 'John Doe',
            'phone' => '08012345678',
        ];

        $response = $this->postJson('/api/v1/vend/entertainment', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $this->assertDatabaseHas('transactions', [
            'type' => 'cable_tv',
            'amount' => 100,
        ]);

        $this->assertEquals('success', $response->json('data.status'));
    }
}
