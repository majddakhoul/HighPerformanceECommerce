<?php

namespace App\Repositories\Contracts;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Role;
    public function create(array $data): Role;
    public function update(Role $role, array $data): Role;
    public function delete(Role $role): bool;
}
