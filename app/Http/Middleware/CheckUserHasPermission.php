<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RoleHasModule;
use App\Models\Module;
use Illuminate\Support\Facades\Route;

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
        $uri = Route::current()->uri();
        $route = explode('/' ,$uri);

        $whiteList = [
            'logs',
            'module',
        ];

        $unverifiedSubRoutes = [
            'approve',
            'reprove',
            'cancel',
        ];

        $routeAccessed = null;

        if('{id}' == $route[count($route)-1]){
            if(in_array($route[count($route)-2], $unverifiedSubRoutes)){
                $routeAccessed = $route[count($route)-3];
            } else{
                $routeAccessed = $route[count($route)-2];
            }
        } else if(in_array($route[count($route)-1], $unverifiedSubRoutes)){
            $routeAccessed = $route[count($route)-2];
        } else{
            $routeAccessed = $route[count($route)-1];
        }

        if(in_array($route[1], $whiteList))
            return $next($request);

        if($user->role_id == 1)
            return $next($request);

        //array de objetos com module id
        $roles = $this->role->where('role_id', $user->role_id)->get(['module_id']);

        if ($request->isMethod('GET')) {
            if (array_key_exists('noAuth', $request->all())){
                return $next($request);
            }
        }

        foreach($roles as $role){
            $routesAllowedByUser = $this->module->where('id', $role->module_id)->get(['route']);

            if($routeAccessed == $routesAllowedByUser[0]->route){
                $role = $this->role->where('role_id', $user->role_id)->where('module_id', $role->module_id)->first();

                if ($request->isMethod('GET')) {
                    if ($role->read == true){
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
                    if(count($route) > 2){
                        if($route[2] == 'import'){
                            if ($role->import == true) {
                                return $next($request);
                            }
                        }
                        if($route[2] == 'export'){
                            if ($role->export == true) {
                                return $next($request);
                            }
                        }
                    }
                    if ($role->create == true) {
                        return $next($request);
                    }
                }
            }


        }
        return response('', 401);
    }
}
