<?php

namespace App\DTOs;

class UpdateUserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $role
    ) {}
    public static function fromRequest($req): self
    {
        return new self(
            $req->id,
            $req->name,
            $req->email,
            $req->role ?? null
        );
    }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role
        ]);
    }
}
