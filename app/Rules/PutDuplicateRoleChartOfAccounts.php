<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\ChartOfAccounts;

class PutDuplicateRoleChartOfAccounts implements Rule
{
    public $parent;
    public $id;

    public function __construct($parent, $id){
        $this->parent = $parent;
        $this->id = $id;
    }

    public function passes($attribute, $value)
    {
        if (ChartOfAccounts::where('code', $value)->where('parent', $this->parent)->where('id', '!=' ,$this->id)->exists()) {
            return false;
        }
        return true;
    }

    public function message()
    {
        return 'Código já cadastrado!';
    }
}
