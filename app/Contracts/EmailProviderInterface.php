<?php

namespace App\Contracts;

use Illuminate\Mail\Mailable;

interface EmailProviderInterface
{
    /**
     * Send an email.
     */
    public function send(string $to, Mailable $mailable): void;

    /**
     * Get the provider name.
     */
    public function getName(): string;
}
