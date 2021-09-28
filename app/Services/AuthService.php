<?php

namespace App\Services;
use App\Models\User;
use App\Models\Module;
use App\Models\RoleHasModule;

class AuthService
{
    private $user;
    private $module;
    private $roleHasModule;

    public function __construct(User $user, Module $module, RoleHasModule $roleHasModule){
        $this->user = $user;
        $this->module = $module;
        $this->roleHasModule = $roleHasModule;
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

        $user->permissions = $permissions;
        $user->access_token = $accessToken;
        return $user;
    }

}



