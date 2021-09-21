<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RoleHasModule;

class CheckUserHasPermission
{
    private $role;

    public function __construct(RoleHasModule $role){
        $this->role = $role;
    }

    public function handle($request, Closure $next)
    {
        $user = $request->user();
        $roles = $this->role->where('role_id', $user->role_id)->first();

        if ($request->isMethod('GET')) {
            if ($roles->read == true) {
                return $next($request);
            }
        }
        if ($request->isMethod('PUT')) {
            if ($roles->update == true) {
                return $next($request);
            }
        }
        if ($request->isMethod('DELETE')) {
            if ($roles->delete == true) {
                return $next($request);
            }
        }
        if ($request->isMethod('POST')) {
            if ($roles->create == true) {
                return $next($request);
            }
        }
        return response('', 401);
    }
}
