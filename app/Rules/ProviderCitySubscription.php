<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ProviderCitySubscription implements Rule
{
    private $international;
    private $subscription;

    public function __construct($international = null, $subscription = null){
        $this->international = $international;
        $this->subscription = $subscription;
    }

    public function passes($attribute, $value)
    {
        if(!$this->international)
        {
            if(!$this->subscription && !$value){
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'O campo city subscription é obrigatório quando state subscription não está presente.';
    }
}
