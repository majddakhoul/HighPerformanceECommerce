<?php

namespace App\DTOs;

class CreateRoleDTO
{
    public function __construct(
        public readonly string $name
    ) {}
    public static function fromRequest($req): self
    {
        return new self($req->name);
    }
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'guard_name' => 'api'
        ];
    }
}
