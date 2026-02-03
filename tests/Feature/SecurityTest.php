<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.lytepay.api_key', 'test_api_key');
        Config::set('services.lytepay.secret', 'test_secret');
    }

    public function test_request_rejected_without_api_key()
    {
        $response = $this->postJson('/api/vend/electricity', []);
        $response->assertStatus(401);
    }

    public function test_request_rejected_with_invalid_api_key()
    {
        $response = $this->withHeader('X-API-KEY', 'wrong_key')
                         ->postJson('/api/vend/electricity', []);
        $response->assertStatus(401);
    }

    public function test_request_rejected_without_signature_or_timestamp()
    {
        $response = $this->withHeader('X-API-KEY', 'test_api_key')
                         ->postJson('/api/vend/electricity', []);
        
        // Should be 400 because of missing headers in VerifySignature
        $response->assertStatus(400); 
    }

    public function test_request_rejected_with_expired_timestamp()
    {
        $payload = json_encode(['meter_number' => '123']);
        $timestamp = time() - 600; // 10 minutes ago
        $signature = hash_hmac('sha256', $payload, 'test_secret');

        $response = $this->withHeaders([
            'X-API-KEY' => 'test_api_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ])->postJson('/api/vend/electricity', ['meter_number' => '123']);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Request timestamp expired.']);
    }

    public function test_request_rejected_with_invalid_signature()
    {
        $payload = json_encode(['meter_number' => '123']);
        $timestamp = time();
        $signature = 'invalid_signature';

        $response = $this->withHeaders([
            'X-API-KEY' => 'test_api_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ])->postJson('/api/vend/electricity', ['meter_number' => '123']);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Invalid request signature.']);
    }

    public function test_validation_fails_with_invalid_data()
    {
        // Valid signature generation helper
        $data = [
            'meter_number' => '', // Invalid
            'amount' => -100, // Invalid
        ];
        $payload = json_encode($data);
        $timestamp = time();
        $signature = hash_hmac('sha256', $payload, 'test_secret');

        $response = $this->withHeaders([
            'X-API-KEY' => 'test_api_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ])->postJson('/api/vend/electricity', $data);

        // Should pass middleware but fail validation
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['meter_number', 'amount']);
    }
}
