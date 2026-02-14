<?php

namespace App\Providers\Mail;

use App\Contracts\MailServiceProviderInterface;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class AwsSesProvider implements MailServiceProviderInterface
{
    protected ?SesClient $client = null;

    public function __construct()
    {
        if ($this->isConfigured() && class_exists(SesClient::class)) {
            $this->initializeClient();
        }
    }

    /**
     * Initialize the AWS SES client
     */
    protected function initializeClient(): void
    {
        $config = config('services.aws_ses', []);

        try {
            $this->client = new SesClient([
                'version' => 'latest',
                'region' => $config['region'] ?? 'us-east-1',
                'credentials' => [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initialize AWS SES client', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function send(array $data): bool
    {
        if (!$this->isConfigured() || !$this->client) {
            Log::warning('AWS SES not configured or client not initialized');
            return false;
        }

        try {
            $result = $this->client->sendEmail($this->formatPayload($data));
            
            Log::info('AWS SES email sent successfully', [
                'message_id' => $result->get('MessageId'),
                'to' => $data['email'],
            ]);

            return true;
        } catch (AwsException $e) {
            Log::error('AWS SES send failed', [
                'error' => $e->getAwsErrorMessage(),
                'code' => $e->getAwsErrorCode(),
                'type' => $e->getAwsErrorType(),
                'to' => $data['email'] ?? null,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('AWS SES unexpected error', [
                'error' => $e->getMessage(),
                'to' => $data['email'] ?? null,
            ]);

            return false;
        }
    }

    public function getName(): string
    {
        return 'aws_ses';
    }

    public function isConfigured(): bool
    {
        $config = config('services.aws_ses', []);

        return !empty($config['key']) 
            && !empty($config['secret']) 
            && !empty($config['region']);
    }

    /**
     * Format the data array into AWS SES payload format
     *
     * @param array $data
     * @return array
     */
    protected function formatPayload(array $data): array
    {
        $payload = [
            'Source' => $data['from']['email'] ?? config('mail.from.address', 'noreply@lythubtechnologies.com'),
            'Destination' => [
                'ToAddresses' => [$data['email']],
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $data['subject'],
                    'Charset' => 'UTF-8',
                ],
                'Body' => [
                    'Text' => [
                        'Data' => $data['text'] ?? strip_tags($data['html'] ?? ''),
                        'Charset' => 'UTF-8',
                    ],
                ],
            ],
        ];

        // Add HTML body if provided
        if (!empty($data['html'])) {
            $payload['Message']['Body']['Html'] = [
                'Data' => $data['html'],
                'Charset' => 'UTF-8',
            ];
        }

        // Add CC recipients
        if (!empty($data['cc'])) {
            $payload['Destination']['CcAddresses'] = is_array($data['cc']) 
                ? $data['cc'] 
                : [$data['cc']];
        }

        // Add BCC recipients
        if (!empty($data['bcc'])) {
            $payload['Destination']['BccAddresses'] = is_array($data['bcc']) 
                ? $data['bcc'] 
                : [$data['bcc']];
        }

        // Add reply-to address
        if (!empty($data['reply_to'])) {
            $payload['ReplyToAddresses'] = is_array($data['reply_to']) 
                ? $data['reply_to'] 
                : [$data['reply_to']];
        }

        return $payload;
    }
}