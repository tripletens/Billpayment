<?php

namespace App\Providers\Mail;

use App\Contracts\MailServiceProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridProvider implements MailServiceProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.sendgrid.api_key', '');
    }

    public function send(array $data): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('SendGrid not configured');
            return false;
        }

        $payload = $this->formatPayload($data);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.sendgrid.com/v3/mail/send', $payload);

        Log::debug('SendGrid response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->successful();
    }

    public function getName(): string
    {
        return 'sendgrid';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    protected function formatPayload(array $data): array
    {
        $personalizations = [
            [
                'to' => [
                    [
                        'email' => $data['email'],
                        'name' => $data['name'] ?? '',
                    ],
                ],
            ],
        ];

        if (!empty($data['cc'])) {
            $personalizations[0]['cc'] = array_map(fn($email) => ['email' => $email], (array) $data['cc']);
        }

        if (!empty($data['bcc'])) {
            $personalizations[0]['bcc'] = array_map(fn($email) => ['email' => $email], (array) $data['bcc']);
        }

        $payload = [
            'personalizations' => $personalizations,
            'from' => [
                'email' => $data['from']['email'] ?? 'noreply@lythubtechnologies.com',
                'name' => $data['from']['name'] ?? 'LytHub Technologies',
            ],
            'subject' => $data['subject'],
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $data['text'] ?? strip_tags($data['html'] ?? ''),
                ],
            ],
        ];

        if (!empty($data['html'])) {
            $payload['content'][] = [
                'type' => 'text/html',
                'value' => $data['html'],
            ];
        }

        if (!empty($data['attachment'])) {
            $payload['attachments'] = [
                [
                    'content' => $data['attachment'],
                    'filename' => $data['filename'] ?? 'document.html',
                    'type' => $data['type'] ?? 'text/html',
                    'disposition' => 'attachment',
                ],
            ];
        }

        return $payload;
    }
}
