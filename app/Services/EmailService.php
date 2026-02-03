<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function send(string $to, $mailable)
    {
        Mail::to($to)->send($mailable);
    }
}
