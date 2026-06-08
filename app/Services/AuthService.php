<?php

namespace App\Services;

use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(protected UserRepositoryInterface $userRepo) {}

    public function register(RegisterDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['password'] = bcrypt($data['password']);
            $user = $this->userRepo->create($data);

            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                throw new \RuntimeException('Role assignment failed: ' . $e->getMessage());
            }

            try {
                $token = auth()->login($user);
            } catch (\Exception $e) {
                throw new \RuntimeException('Token generation failed: ' . $e->getMessage());
            }

            return ['user' => $user, 'token' => $token];
        });
    }

    public function login(LoginDTO $dto): string
    {
        if (!$token = auth()->attempt(['email' => $dto->email, 'password' => $dto->password])) {
            throw new \Exception('Invalid credentials', 401);
        }
        return $token;
    }
}
