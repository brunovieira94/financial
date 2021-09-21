<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RoleHasModule;
use App\Models\Module;

class CheckUserHasPermission
{
    private $role;
    private $module;

    public function __construct(RoleHasModule $role, Module $module){
        $this->role = $role;
        $this->module = $module;
    }

    public function handle($request, Closure $next)
    {
        $user = $request->user();
        $uri = $request->path();
        $route = explode('/' ,$uri);

        //array de objetos com module id
        $roles = $this->role->where('role_id', $user->role_id)->get(['module_id']);

        foreach($roles as $role){
            $routesAllowedByUser = $this->module->where('id', $role->module_id)->get(['route']);

            if($route[1] == $routesAllowedByUser[0]->route){
                $role = $this->role->where('role_id', $user->role_id)->where('module_id', $role->module_id)->first();

                if ($request->isMethod('GET')) {
                    if ($role->read == true) {
                        return $next($request);
                    }
                }
                if ($request->isMethod('PUT')) {
                    if ($role->update == true) {
                        return $next($request);
                    }
                }
                if ($request->isMethod('DELETE')) {
                    if ($role->delete == true) {
                        return $next($request);
                    }
                }
                if ($request->isMethod('POST')) {
                    if ($role->create == true) {
                        return $next($request);
                    }
                }
            }


        }






      //$modules = $this->module->find($roles['module_id']);


        return response('', 401);
    }
}
