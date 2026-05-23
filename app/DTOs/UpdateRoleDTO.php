<?php

namespace App\DTOs;

class UpdateRoleDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name
    ) {}
    public static function fromRequest($req): self
    {
        return new self($req->id, $req->name);
    }
    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
