<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Termii SMS Service.
 *
 * Controlled by TERMII_ENABLED in .env.
 * When disabled (default), all sends are logged and silently skipped.
 * To activate: set TERMII_ENABLED=true and add TERMII_API_KEY in .env.
 */
class TermiiService
{
    protected bool $enabled;
    protected string $apiKey;
    protected string $senderId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->enabled  = (bool) config('services.termii.enabled', false);
        $this->apiKey   = (string) config('services.termii.api_key', '');
        $this->senderId = (string) config('services.termii.sender_id', 'BillPay');
        $this->baseUrl  = (string) config('services.termii.base_url', 'https://api.ng.termii.com/api');
    }

    /**
     * Send an SMS message.
     *
     * @param  string  $to       Phone number in international format e.g. 2348012345678
     * @param  string  $message  SMS body text
     * @return array             ['sent' => bool, 'message' => string]
     */
    public function sendSms(string $to, string $message): array
    {
        if (! $this->enabled) {
            Log::info('Termii SMS skipped (disabled)', [
                'to'      => $to,
                'message' => $message,
            ]);

            return ['sent' => false, 'message' => 'Termii SMS is disabled (TERMII_ENABLED=false).'];
        }

        if (empty($this->apiKey)) {
            Log::warning('Termii SMS skipped: no API key configured.');

            return ['sent' => false, 'message' => 'Termii API key not configured.'];
        }

        try {
            $response = Http::post($this->baseUrl . '/sms/send', [
                'to'      => $this->normalizePhone($to),
                'from'    => $this->senderId,
                'sms'     => $message,
                'type'    => 'plain',
                'channel' => 'generic',
                'api_key' => $this->apiKey,
            ]);

            $body = $response->json();

            if ($response->successful() && ($body['code'] ?? '') === 'ok') {
                Log::info('Termii SMS sent', ['to' => $to, 'message_id' => $body['message_id'] ?? null]);

                return ['sent' => true, 'message' => 'SMS sent successfully.', 'data' => $body];
            }

            Log::warning('Termii SMS failed', ['to' => $to, 'response' => $body]);

            return ['sent' => false, 'message' => $body['message'] ?? 'Unknown Termii error.', 'data' => $body];

        } catch (\Exception $e) {
            Log::error('Termii SMS exception', ['to' => $to, 'error' => $e->getMessage()]);

            return ['sent' => false, 'message' => 'SMS send failed: ' . $e->getMessage()];
        }
    }

    /**
     * Send a payment receipt SMS.
     */
    public function sendPaymentReceiptSms(string $phone, string $reference, float $amount, string $type): array
    {
        $message = "BillPay: Your {$type} payment of ₦" . number_format($amount, 2)
            . " was successful. Ref: {$reference}. Thank you!";

        return $this->sendSms($phone, $message);
    }

    /**
     * Normalise Nigerian phone number to international format.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '234' . substr($phone, 1);
        }

        return $phone;
    }
}
