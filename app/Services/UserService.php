<?php

namespace App\Services;

use App\DTOs\UpdateUserDTO;
use App\DTOs\DeleteUserDTO;
use App\DTOs\AssignRoleToUserDTO;
use App\DTOs\RemoveRoleFromUserDTO;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(protected UserRepositoryInterface $userRepo) {}

    public function getAllUsers(): \Illuminate\Support\Collection
    {
        return $this->userRepo->all();
    }

    public function getAuthenticatedUser(User $authUser): User
    {
        return $authUser->load('roles');
    }

    public function updateUser(UpdateUserDTO $dto, User $authUser): User
    {
        $user = $this->userRepo->find($dto->id);
        if (!$user) throw new \Exception('User not found', 404);

        if (!$authUser->hasRole('admin') && $authUser->id !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }

        $data = $dto->toArray();

        if (!$authUser->hasRole('admin')) {
            unset($data['role']);
        }

        return $this->userRepo->update($user, $data);
    }

    public function deleteUser(DeleteUserDTO $dto, User $authUser): bool
    {
        $user = $this->userRepo->find($dto->id);
        if (!$user) throw new \Exception('User not found', 404);

        if (!$authUser->hasRole('admin')) {
            throw new \Exception('Unauthorized', 403);
        }

        return $this->userRepo->delete($user);
    }

    public function assignRoleToUser(AssignRoleToUserDTO $dto, User $authUser): User
    {
        if (!$authUser->hasRole('admin')) {
            throw new \Exception('Unauthorized', 403);
        }

        $user = $this->userRepo->find($dto->userId);
        $role = Role::findById($dto->roleId, 'api');
        if (!$user || !$role) throw new \Exception('User or role not found', 404);

        $user->assignRole($role);
        return $user->load('roles');
    }

    public function removeRoleFromUser(RemoveRoleFromUserDTO $dto, User $authUser): User
    {
        if (!$authUser->hasRole('admin')) {
            throw new \Exception('Unauthorized', 403);
        }

        $user = $this->userRepo->find($dto->userId);
        $role = Role::findById($dto->roleId, 'api');
        if (!$user || !$role) throw new \Exception('User or role not found', 404);

        $user->removeRole($role);
        return $user->load('roles');
    }
}
