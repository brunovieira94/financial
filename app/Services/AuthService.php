<?php

namespace App\Services;

use App\Models\Module;
use App\Models\RoleHasModule;
use App\Models\Role;
use App\Models\User;

class AuthService
{
    private $module;
    private $role;
    private $roleHasModule;
    private $user;


    public function __construct(Module $module, RoleHasModule $roleHasModule, Role $role, User $user){
        $this->module = $module;
        $this->roleHasModule = $roleHasModule;
        $this->role = $role;
        $this->user = $user;
    }

    public function getUser($id, $tokenResponse)
    {
        $user = $this->user->findOrFail($id);

        $permissions = $this->roleHasModule->with('module')->where('role_id', $user->role_id);

        $permissions = $permissions->whereHas('module', function ($query) {
            $query->where('active', true);
        })->get(['create', 'read', 'update', 'delete', 'import', 'export', 'module_id']);

        foreach($permissions as $permission){
            $this->module->withoutAppends = true;
            $module = $this->module->where('id', $permission->module_id)->get(['route'])->first();
            unset($permission->module_id);
            $permission->route = $module->route;
        }

        $user->role = $this->role->where('id', $user->role_id)->get(['id', 'title', 'transfer_approval'])->first();

        unset($user->role_id);
        $user->permissions = $permissions;

        return response(['user' => $user, 'access_token' => $tokenResponse->access_token, 'refresh_token' => $tokenResponse->refresh_token]);
    }

}



