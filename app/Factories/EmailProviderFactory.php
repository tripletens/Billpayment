<?php

namespace App\Factories;

use App\Contracts\EmailProviderInterface;
use App\Providers\Email\SMTPEmailProvider;
use App\Providers\Email\SendGridEmailProvider;
use App\Providers\Email\MailgunEmailProvider;
use InvalidArgumentException;

class EmailProviderFactory
{
    protected array $providers = [
        'smtp' => SMTPEmailProvider::class,
        'sendgrid' => SendGridEmailProvider::class,
        'mailgun' => MailgunEmailProvider::class,
    ];

    /**
     * Create an email provider instance.
     */
    public function make(?string $provider = null): EmailProviderInterface
    {
        $provider = $provider ?? config('mail.default', 'smtp');

        if (!isset($this->providers[$provider])) {
            throw new InvalidArgumentException("Unsupported email provider: {$provider}");
        }

        return app($this->providers[$provider]);
    }

    /**
     * Get list of available providers.
     */
    public function availableProviders(): array
    {
        return array_keys($this->providers);
    }
}
