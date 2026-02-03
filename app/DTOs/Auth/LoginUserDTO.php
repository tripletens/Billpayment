<?php

namespace App\DTOs\Auth;

class LoginUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}
}
