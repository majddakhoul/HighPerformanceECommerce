<?php

namespace App\DTOs;

class RemoveRoleFromUserDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $roleId
    ) {}
    public static function fromRequest($req): self
    {
        return new self(
            $req->user_id,
            $req->role_id
        );
    }
}
