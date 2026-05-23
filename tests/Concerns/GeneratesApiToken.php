<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

trait GeneratesApiToken
{
    public function generateTokenForUser(User $user): string
    {
        return JWTAuth::fromUser($user);
    }
}
