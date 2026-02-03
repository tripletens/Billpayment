<?php

namespace App\Providers\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;

class SendGridEmailProvider implements EmailProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.sendgrid.api_key');
    }

    public function send(string $to, Mailable $mailable): void
    {
        // Render the mailable to get content
        $mailable->to($to);
        $rendered = $mailable->render();

        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.sendgrid.com/v3/mail/send', [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                ],
            ],
            'from' => [
                'email' => config('mail.from.address'),
                'name' => config('mail.from.name'),
            ],
            'subject' => $mailable->subject ?? 'No Subject',
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $rendered,
                ],
            ],
        ]);
    }

    public function getName(): string
    {
        return 'sendgrid';
    }
}
