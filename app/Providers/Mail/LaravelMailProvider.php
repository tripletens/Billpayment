<?php

namespace App\Providers\Mail;

use App\Contracts\MailServiceProviderInterface;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LaravelMailProvider implements MailServiceProviderInterface
{
    public function send(array $data): bool
    {
        try {
            Mail::send(function (Message $message) use ($data) {
                $message->to($data['email'], $data['name'] ?? '')
                    ->subject($data['subject']);

                if (!empty($data['from'])) {
                    $message->from(
                        $data['from']['email'] ?? 'noreply@lythubtechnologies.com',
                        $data['from']['name'] ?? 'LytHub Technologies'
                    );
                }

                if (!empty($data['cc'])) {
                    $message->cc((array) $data['cc']);
                }

                if (!empty($data['bcc'])) {
                    $message->bcc((array) $data['bcc']);
                }

                if (!empty($data['reply_to'])) {
                    $replyTo = is_array($data['reply_to'])
                        ? reset($data['reply_to'])
                        : $data['reply_to'];
                    $message->replyTo($replyTo);
                }

                if (!empty($data['html'])) {
                    $message->html($data['html']);
                }

                if (!empty($data['text'])) {
                    $message->text($data['text']);
                }

                if (!empty($data['attachment'])) {
                    $message->attachData(
                        base64_decode($data['attachment']),
                        $data['filename'] ?? 'document.html',
                        ['mime' => $data['type'] ?? 'text/html']
                    );
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Laravel Mail send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function getName(): string
    {
        return 'laravel_mail';
    }

    public function isConfigured(): bool
    {
        // Laravel Mail is always available (falls back to log or database driver)
        return true;
    }
}
