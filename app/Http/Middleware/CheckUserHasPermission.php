<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\RoleHasModule;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class CheckUserHasPermission
{
    private $role;
    private $module;

    public function __construct(RoleHasModule $role, Module $module)
    {
        $this->role = $role;
        $this->module = $module;
    }

    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if ($user->logged_user_id != null) {
            $user = User::with(['role'])->findOrFail($user->logged_user_id);
        }
        $uri = Route::current()->uri();
        $url = explode('/', $request->url());
        $route = explode('/', $uri);

        $whiteList = [
            'logs',
            'module',
            'cnab',
            'hotel-cnab',
            'update-user',
            'update-date-installment',
            'delivery',
            'purchase-order-export',
            'change-logged-user',
            'pluto-table-state',
        ];

        $unverifiedSubRoutes = [
            'approve',
            'reprove',
            'cancel',
            'group-form-payment',
            'all',
            'filter-user',
            'approve-many',
            'update-installment',
            'multiple-approval',
            'transfer-approval',
            'listinvoice',
            'getinvoice',
            'get-users'
        ];

        $routeAccessed = $route[count($route) - 1];


        if ('{id}' == $routeAccessed) {
            $routeAccessed = $route[count($route) - 2];
            if ($routeAccessed == 'export' || $routeAccessed == 'import') {
                $routeAccessed = $route[count($route) - 3];
            } else {
                if (in_array($route[count($route) - 2], $unverifiedSubRoutes)) {
                    $routeAccessed = $route[count($route) - 3];
                }
            }
        } else if (in_array($routeAccessed, $unverifiedSubRoutes)) {
            $routeAccessed = $route[count($route) - 2];
        } else if ($routeAccessed == 'export' || $routeAccessed == 'import') {
            $routeAccessed = $route[count($route) - 2];
        } else if ($routeAccessed == '{approvalStatus}') {
            $routeAccessed = $url[count($url) - 1];
        }

        if (in_array($route[1], $whiteList))
            return $next($request);

        if ($user->role_id == 1)
            return $next($request);

        //array de objetos com module id
        $roles = $this->role->where('role_id', $user->role_id);

        $roles = $roles->whereHas('module', function ($query) {
            $query->where('active', true);
        })->get(['module_id']);

        switch ($routeAccessed) {
            case 'approved-installment':
                $routeAccessed = 'approved-payment-request';
                break;
            case 'installments-payable':
                $routeAccessed = 'payment-requests-paid';
                break;
            case 'due-installments':
                $routeAccessed = 'due-bills';
                break;
            case 'cangooroo':
            case 'show':
            case 'refresh':
            case 'approval-roles':
                $routeAccessed = 'billing';
                break;
            case 'approved-purchase-order-integration';
                $routeAccessed = 'payment-request';
            case 'bank-account';
                $routeAccessed = 'provider';
                break;
            case 'installment';
                $routeAccessed = 'payment-request';
                break;
            // case 'billing-rejected':
            // case 'billing-open':
            //     $routeAccessed = 'billing';
            //     break;
        }

        foreach ($roles as $role) {
            $routesAllowedByUser = $this->module->where('id', $role->module_id)->get(['route']);
            $role = $this->role->where('role_id', $user->role_id)->where('module_id', $role->module_id)->first();
            if ($request->isMethod('GET') && $routeAccessed == 'approved-purchase-order' && $routesAllowedByUser[0]->route == 'payment-request' && ($role->update || $role->create)) {
                return $next($request);
            }
            if ($routeAccessed == $routesAllowedByUser[0]->route) {
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
                    if (count($route) > 2) {
                        if ($route[count($route) - 1] == 'import') {
                            if ($role->import == true) {
                                return $next($request);
                            }
                        }
                        if ($route[count($route) - 1] == 'export') {
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
        return response('', 403);
    }
}
