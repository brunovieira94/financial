<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\PutRoleRequest;
use App\Services\RoleService as RoleService;

class RoleController extends Controller
{

    private $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        return $this->roleService->getAllRole();
    }

    public function show($id)
    {
        return $this->roleService->getRole($id);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = $this->roleService->postRole($request->all());
        return response($role, 201);
    }

    public function update(PutRoleRequest $request, $id)
    {
        return $this->roleService->putRole($id, $request->all());
    }

    public function destroy($id)
    {
        $role = $this->roleService->deleteRole($id);
        return response('');
    }
}