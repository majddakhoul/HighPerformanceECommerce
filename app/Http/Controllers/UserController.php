<?php
namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\UserService;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\AssignRoleToUserRequest;
use App\Http\Requests\RemoveRoleFromUserRequest;
use App\DTOs\UpdateUserDTO;
use App\DTOs\DeleteUserDTO;
use App\DTOs\AssignRoleToUserDTO;
use App\DTOs\RemoveRoleFromUserDTO;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService) {}

    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = $this->userService->getAllUsers();
        return $this->success(UserResource::collection($users));
    }

    public function update(UpdateUserRequest $request)
    {
        try {
            $dto = UpdateUserDTO::fromRequest($request);
            $user = $this->userService->updateUser($dto, Auth::user());
            return $this->success(new UserResource($user), 'User updated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function destroy(DeleteUserRequest $request)
    {
        try {
            $dto = DeleteUserDTO::fromRequest($request);
            $this->userService->deleteUser($dto, Auth::user());
            return $this->success(null, 'User deleted');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    public function assignRole(AssignRoleToUserRequest $request)
    {
        try {
            $dto = AssignRoleToUserDTO::fromRequest($request);
            $user = $this->userService->assignRoleToUser($dto, Auth::user());
            return $this->success(new UserResource($user), 'Role assigned');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }

    public function removeRole(RemoveRoleFromUserRequest $request)
    {
        try {
            $dto = RemoveRoleFromUserDTO::fromRequest($request);
            $user = $this->userService->removeRoleFromUser($dto, Auth::user());
            return $this->success(new UserResource($user), 'Role removed');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }
}