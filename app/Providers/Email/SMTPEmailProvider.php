<?php

namespace App\Providers\Email;

use App\Contracts\EmailProviderInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SMTPEmailProvider implements EmailProviderInterface
{
    public function send(string $to, Mailable $mailable): void
    {
        Mail::to($to)->send($mailable);
    }

    public function getName(): string
    {
        return 'smtp';
    }
}
