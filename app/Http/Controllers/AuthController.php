<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        try {
            $dto = RegisterDTO::fromRequest($request);
            $result = $this->authService->register($dto);
            return $this->success([
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Registered successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $dto = LoginDTO::fromRequest($request);
            $token = $this->authService->login($dto);
            return $this->success(['token' => $token], 'Logged in');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function me()
    {
        return $this->success(new UserResource(Auth::user()));
    }

    public function logout()
    {
        Auth::logout();
        return $this->success(null, 'Logged out');
    }

    public function refresh()
    {
        return $this->success(['token' => Auth::refresh()]);
    }
}
