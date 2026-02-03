<?php

namespace App\DTOs\Auth;

class ForgotPasswordDTO
{
    public function __construct(
        public readonly string $email
    ) {}
}
