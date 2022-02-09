<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ProviderStateSubscription implements Rule
{
    private $international;
    private $subscription;
    private $type;

    public function __construct($international = null, $subscription = null, $type = null){
        $this->international = $international;
        $this->subscription = $subscription;
        $this->type = $type;
    }

    public function passes($attribute, $value)
    {
        if(!$this->international)
        {
            if(!$this->subscription && !$value && $this->type == 'J'){
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'O campo state subscription é obrigatório quando city subscription não está presente.';
    }
}
