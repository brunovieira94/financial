<?php

namespace App\Scopes;

use App\Models\Role;
use App\Models\User;
use App\Models\UserHasCostCenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProfileCostCenterScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->user() != null) {

            if (Role::findOrFail(auth()->user()->role_id)->filter_cost_center) {
                if (auth()->user()->logged_user_id == null) {
                    $costCenter = auth()->user()->cost_center->pluck('id');
                    $builder->whereIn('cost_center_id', $costCenter);
                } else {
                    $costCenter = User::with('cost_center')->findOrFail(auth()->user()->logged_user_id)->cost_center->pluck('id');
                    $builder->whereIn('cost_center_id', $costCenter);
                }
            }
        }
    }
}
