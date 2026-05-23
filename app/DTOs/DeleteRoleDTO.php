<?php

namespace App\DTOs;

class DeleteRoleDTO
{
    public function __construct(public readonly int $id) {}
    public static function fromRequest($req): self
    {
        return new self($req->id);
    }
}
