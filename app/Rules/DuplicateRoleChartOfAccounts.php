<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;
use App\Models\ChartOfAccounts;

class DuplicateRoleChartOfAccounts implements Rule
{

    public $parent;

    public function __construct($parent){
        $this->parent = $parent;
    }

    public function passes($attribute, $value)
    {
        if (ChartOfAccounts::where('code', $value)->where('parent', $this->parent)->exists()) {
            return false;
        }
        return true;
    }

    public function message()
    {
        return 'Código já cadastrado!';
    }
}
