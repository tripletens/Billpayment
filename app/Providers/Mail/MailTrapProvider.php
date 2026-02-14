<?php

namespace App\Providers\Mail;

use App\Contracts\MailServiceProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailTrapProvider implements MailServiceProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $config = config('services.mailtrap', []);
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? ($config['base_url'] ?? 'https://send.api.mailtrap.io');
    }

    public function send(array $data): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('MailTrap not configured');
            return false;
        }

        $payload = $this->formatPayload($data);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Api-Token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl, $payload);

        Log::debug('MailTrap response', $response->json());

        return $response->successful();
    }

    public function getName(): string
    {
        return 'mailtrap';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    protected function formatPayload(array $data): array
    {
        return [
            'to' => [[
                'email' => $data['email'],
                'name' => $data['name'] ?? '',
            ]],
            'cc' => $data['cc'] ?? [],
            'bcc' => $data['bcc'] ?? [],
            'from' => [
                'email' => $data['from']['email'] ?? 'noreply@lythubtechnologies.com',
                'name' => $data['from']['name'] ?? 'LytHub Technologies',
            ],
            'reply_to' => $data['reply_to'] ?? [],
            'subject' => $data['subject'],
            'text' => $data['text'] ?? strip_tags($data['html'] ?? ''),
            'html' => $data['html'] ?? null,
            'category' => $data['category'] ?? 'general',
            'attachments' => $this->prepareAttachments($data),
            'headers' => [
                'X-Message-Source' => 'lythubtechnologies.com',
            ],
        ];
    }

    protected function prepareAttachments(array $data): array
    {
        if (!empty($data['attachment'])) {
            return [[
                'content' => $data['attachment'],
                'filename' => $data['filename'] ?? 'document.html',
                'type' => $data['type'] ?? 'text/html',
                'disposition' => 'attachment',
            ]];
        }

        return [];
    }
}
