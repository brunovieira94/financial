<?php

namespace App\Services;
use App\Models\State;
use App\Models\City;

class StateService
{
    private $state;
    private $city;
    public function __construct(State $state, City $city)
    {
        $this->state = $state;
        $this->city = $city;
    }

    public function getAllState($requestInfo)
    {
        $state = Utils::search($this->state,$requestInfo);
        return Utils::pagination($state->with('country'),$requestInfo);
    }

    public function getState($id)
    {
      return $this->state->with('country')->findOrFail($id);
    }

    public function postState($stateInfo)
    {
        $state = new State;
        $state = $state->create($stateInfo);
        return $this->state->with('country')->findOrFail($state->id);
    }

    public function putState($id, $stateInfo)
    {
        $state = $this->state->findOrFail($id);
        $state->fill($stateInfo)->save();
        return $this->state->with('country')->findOrFail($state->id);
    }

    public function deleteState($id)
    {
      $this->state->findOrFail($id)->delete();
      return true;
    }

}
