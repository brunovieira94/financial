<?php

namespace App\Services;

use App\Models\AdditionalUser;
use App\Models\Module;
use App\Models\RoleHasModule;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAuth;
use Auth;
use Response;

class AuthService
{
    private $module;
    private $role;
    private $roleHasModule;
    private $user;


    public function __construct(Module $module, RoleHasModule $roleHasModule, Role $role, UserAuth $user)
    {
        $this->module = $module;
        $this->roleHasModule = $roleHasModule;
        $this->role = $role;
        $this->user = $user;
    }

    public function getUser($id, $tokenResponse)
    {
        $users = User::whereNotNull('return_date')
            ->where('return_date', '<', date('Y-m-d H:i:s'))
            ->where('status', '!=', 2)
            ->where('id', $id)
            ->get();

        foreach ($users as $user) {
            activity()->disableLogging();
            AdditionalUser::where('user_additional_id', $id)->delete();
            User::where('id', $id)
                ->update([
                    'return_date' => null,
                    'status' => 0
                ]);
            activity()->enableLogging();
        }

        $user = $this->user->with(['role', 'additional_users.role', 'cost_center', 'business', 'filters'])->findOrFail($id);

        if ($user->status != 0) {
            return response()->json([
                'error' => 'O usuário encontra-se desativado, suspenso ou em férias.'
            ], 422);
        }

        $permissions = $this->roleHasModule->with('module')->where('role_id', $user->role_id);

        $permissions = $permissions->whereHas('module', function ($query) {
            $query->where('active', true);
        })->get(['create', 'read', 'update', 'delete', 'import', 'export', 'module_id']);

        foreach ($permissions as $permission) {
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

    public function changeLogin($request, $id)
    {
        if (User::with('additional_users')
            ->where('id', auth()->user()->id)
            ->whereHas('additional_users', function ($query) use ($id) {
                $query->where('user_additional_id', $id);
            })->exists()
        ) {
            Auth::user()
                ->update(
                    [
                        'logged_user_id' => $id
                    ]
                );
            return Response()->json(
                [],
                200
            );
        } else {
            return Response()->json([
                'error' => 'Falha ao logar com outro usuário, verifique as configurações do usuário.'
            ], 430);
        }
    }
}
