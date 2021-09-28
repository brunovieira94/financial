<?php

namespace App\Services;

use App\Models\Module;
use App\Models\RoleHasModule;
use App\Models\Role;

class AuthService
{

    private $module;
    private $role;
    private $roleHasModule;


    public function __construct(Module $module, RoleHasModule $roleHasModule, Role $role){
        $this->module = $module;
        $this->roleHasModule = $roleHasModule;
        $this->role = $role;
    }

    public function getUser($user, $accessToken)
    {
        $permissions = $this->roleHasModule->where('role_id', $user->role_id)->get(['create', 'read', 'update', 'delete', 'import', 'export', 'module_id']);

        foreach($permissions as $permission){
            $this->module->withoutAppends = true;
            $module = $this->module->where('id', $permission->module_id)->get(['route'])->first();
            unset($permission->module_id);
            $permission->route = $module->route;
        }

        $user->role = $this->role->where('id', $user->role_id)->get(['id', 'title'])->first();

        unset( $user->role_id);
        $user->permissions = $permissions;

        return response(['user' => $user, 'access_token' => $accessToken]);
    }

}



