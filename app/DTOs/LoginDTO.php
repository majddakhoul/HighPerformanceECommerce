<?php

namespace App\DTOs;

class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}

    public static function fromRequest($req):self
    {
        return new self(
            $req->email,
            $req->password
        );
    }

    public function toArray():array
    {
        return [
            'email' => $this->email,
            'password' => $this->password
        ];
    }
}
