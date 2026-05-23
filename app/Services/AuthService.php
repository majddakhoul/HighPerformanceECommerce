<?php

namespace App\Services;

use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Repositories\Contracts\UserRepositoryInterface;

class AuthService
{
    public function __construct(protected UserRepositoryInterface $userRepo) {}

    public function register(RegisterDTO $dto): array
    {
        $data = $dto->toArray();
        $data['password'] = bcrypt($data['password']);
        $user = $this->userRepo->create($data);
        $user->assignRole('user');
        $token = auth()->login($user);
        return ['user' => $user, 'token' => $token];
    }

    public function login(LoginDTO $dto): string
    {
        if (!$token = auth()->attempt(['email' => $dto->email, 'password' => $dto->password])) {
            throw new \Exception('Invalid credentials', 401);
        }
        return $token;
    }
}