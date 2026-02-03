<?php

namespace App\Providers\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;

class MailgunEmailProvider implements EmailProviderInterface
{
    protected string $domain;
    protected string $apiKey;

    public function __construct()
    {
        $this->domain = config('services.mailgun.domain');
        $this->apiKey = config('services.mailgun.secret');
    }

    public function send(string $to, Mailable $mailable): void
    {
        // Render the mailable to get content
        $mailable->to($to);
        $rendered = $mailable->render();

        Http::withBasicAuth('api', $this->apiKey)
            ->asMultipart()
            ->post("https://api.mailgun.net/v3/{$this->domain}/messages", [
                ['name' => 'from', 'contents' => config('mail.from.name') . ' <' . config('mail.from.address') . '>'],
                ['name' => 'to', 'contents' => $to],
                ['name' => 'subject', 'contents' => $mailable->subject ?? 'No Subject'],
                ['name' => 'html', 'contents' => $rendered],
            ]);
    }

    public function getName(): string
    {
        return 'mailgun';
    }
}
