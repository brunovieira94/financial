<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRoleRequest;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\PutRoleRequest;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowSupply;
use App\Models\User;
use App\Services\RoleService as RoleService;

class RoleController extends Controller
{

    private $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(Request $request)
    {
        return $this->roleService->getAllRole($request->all());
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
        if(ApprovalFlow::where('role_id', $id)->exists() OR ApprovalFlowSupply::where('role_id', $id)->exists())
        {
            return response()->json([
                'erro' => 'Este perfil está sendo utilizado no fluxo de aprovação é necessário que remova antes de apagar.'
            ], 422);
        }
        if(User::where('role_id', $id)->exists())
        {
            return response()->json([
                'erro' => 'Este prfil está sendo utilizado por usuário(s) no sistema é necessário que remova antes de apagar.'
            ], 422);
        }

        $role = $this->roleService->deleteRole($id);
        return response('');
    }
}
