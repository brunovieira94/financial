<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\City;

class DuplicateCity implements Rule
{
    private $states_id;
    private $cityID;

    public function __construct($states_id = null, $cityID = null){
        $this->states_id = $states_id;
        $this->cityID = $cityID;
    }

    public function passes($attribute, $value)
    {
        if(is_null($this->states_id)){
            $city = City::with('state')
            ->findOrFail($this->cityID);
            $this->states_id = $city->state->id;
        }

        if(!is_null($this->cityID)){
            $city = City::with('state')
            ->findOrFail($this->cityID);

            if($city->title == $value && $city->states_id == $this->states_id)
            {
                return true;
            }

            if($city->title == $value){
                if(City::with('state')
                ->where($attribute, $value)
                ->whereRelation('state', 'id', '=', $this->states_id)
                ->exists()){
                   return false;
                };
                return true;
            }
        }

        if(City::with('state')
        ->where($attribute, $value)
        ->whereRelation('state', 'id', '=', $this->states_id)
        ->exists()){
            return false;
        };
        return true;
    }

    public function message()
    {
        return 'Cidade já cadastrada no banco de dados';
    }
}
