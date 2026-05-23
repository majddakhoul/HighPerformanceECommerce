<?php

namespace App\DTOs;

class DeleteUserDTO
{
    public function __construct(public readonly int $id) {}
    public static function fromRequest($req): self
    {
        return new self($req->id);
    }
}
