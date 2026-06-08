<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\RoleService;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\DeleteRoleRequest;
use App\DTOs\CreateRoleDTO;
use App\DTOs\UpdateRoleDTO;
use App\DTOs\DeleteRoleDTO;
use App\Http\Resources\RoleResource;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(protected RoleService $roleService) {}

    public function index()
    {
        $this->authorize('viewAny', Role::class);
        $roles = $this->roleService->getAllRoles();
        return $this->success(RoleResource::collection($roles));
    }

    public function store(CreateRoleRequest $request)
    {
        $this->authorize('create', Role::class);
        $dto = CreateRoleDTO::fromRequest($request);
        $role = $this->roleService->createRole($dto);
        return $this->success(new RoleResource($role), 'Role created', 201);
    }

    public function update(UpdateRoleRequest $request)
    {
        $this->authorize('update', Role::class);
        $dto = UpdateRoleDTO::fromRequest($request);
        $role = $this->roleService->updateRole($dto);
        return $this->success(new RoleResource($role), 'Role updated');
    }

    public function destroy(DeleteRoleRequest $request)
    {
        $this->authorize('delete', Role::class);
        $dto = DeleteRoleDTO::fromRequest($request);
        $this->roleService->deleteRole($dto);
        return $this->success(null, 'Role deleted');
    }
}
