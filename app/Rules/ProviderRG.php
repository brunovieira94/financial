<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ProviderRG implements Rule
{
    private $international;
    private $type;

    public function __construct($international = null, $type = null){
        $this->international = $international;
        $this->type = $type;
    }

    public function passes($attribute, $value)
    {
        if(!$this->international)
        {
            if($this->type == 'F' && $value == null){
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'O campo rg é obrigatório quando provider type for F.';
    }
}
