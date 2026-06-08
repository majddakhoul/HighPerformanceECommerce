<?php

namespace App\Services;

use App\DTOs\CreateRoleDTO;
use App\DTOs\UpdateRoleDTO;
use App\DTOs\DeleteRoleDTO;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class RoleService
{
    public function __construct(protected RoleRepositoryInterface $roleRepo) {}

    public function getAllRoles(): \Illuminate\Support\Collection
    {
        if (Gate::denies('viewAny', \Spatie\Permission\Models\Role::class)) {
            throw new \Exception('Unauthorized', 403);
        }
        return $this->roleRepo->all();
    }

    public function createRole(CreateRoleDTO $dto): \Spatie\Permission\Models\Role
    {
        if (Gate::denies('create', \Spatie\Permission\Models\Role::class)) {
            throw new \Exception('Unauthorized', 403);
        }
        return $this->roleRepo->create($dto->toArray());
    }

    public function updateRole(UpdateRoleDTO $dto): \Spatie\Permission\Models\Role
    {
        $role = $this->roleRepo->find($dto->id);
        if (!$role) throw new \Exception('Role not found', 404);
        if (Gate::denies('update', $role)) throw new \Exception('Unauthorized', 403);
        return $this->roleRepo->update($role, ['name' => $dto->name]);
    }

    public function deleteRole(DeleteRoleDTO $dto): bool
    {
        $role = $this->roleRepo->find($dto->id);
        if (!$role) throw new \Exception('Role not found', 404);
        if (Gate::denies('delete', $role)) throw new \Exception('Unauthorized', 403);
        return $this->roleRepo->delete($role);
    }
}
