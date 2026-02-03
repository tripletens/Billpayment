<?php

namespace App\DTOs\Auth;

class ResetPasswordDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $password
    ) {}    

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'],
            $data['token'],
            $data['password']
        );
    }
    
    
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'token' => $this->token,
            'password' => $this->password,
        ];
    }
}
