<?php

namespace App\Repositories\Eloquent;

use Spatie\Permission\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;

class RoleRepository implements RoleRepositoryInterface
{
    public function all(): Collection
    {
        return Role::all();
    }
    public function find(int $id): ?Role
    {
        return Role::findById($id, 'api');
    }
    public function create(array $data): Role
    {
        return Role::create($data);
    }
    public function update(Role $role, array $data): Role
    {
        $role->update($data);
        return $role;
    }
    public function delete(Role $role): bool
    {
        return $role->delete();
    }
}
