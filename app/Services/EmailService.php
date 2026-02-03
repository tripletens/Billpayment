<?php

namespace App\Services;

use App\Factories\EmailProviderFactory;
use Illuminate\Mail\Mailable;

class EmailService
{
    public function __construct(
        protected EmailProviderFactory $providerFactory
    ) {}

    public function send(string $to, Mailable $mailable, ?string $providerName = null): void
    {
        $provider = $this->providerFactory->make($providerName);
        $provider->send($to, $mailable);
    }
}
