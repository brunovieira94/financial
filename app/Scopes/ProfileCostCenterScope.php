<?php

namespace App\Scopes;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProfileCostCenterScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if(Role::findOrFail(auth()->user()->role_id)->filter_cost_center)
        {
            $costCenter = auth()->user()->cost_center->pluck('id');
            $builder->whereIn('cost_center_id', $costCenter);
        }
    }
}
