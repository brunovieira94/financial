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
        return Utils::pagination($state,$requestInfo);
    }

    public function getState($id)
    {
      return $this->state->findOrFail($id);
    }

    public function postState($stateInfo)
    {
        $state = new State;
        return $state->create($stateInfo);
    }

    public function putState($id, $stateInfo)
    {
        $state = $this->state->findOrFail($id);
        $state->fill($stateInfo)->save();
        return $state;
    }

    public function deleteState($id)
    {
      $collection = $this->city->where('states_id', $id)->get(['id']);
      $this->city->destroy($collection->toArray());
      $this->state->findOrFail($id)->delete();
      return true;
    }

}
