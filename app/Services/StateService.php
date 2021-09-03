<?php

namespace App\Services;
use App\Models\State;

class StateService
{
    private $state;
    public function __construct(State $state)
    {
        $this->state = $state;
    }

    public function getAllState()
    {
        return $this->state->get();
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
      $this->state->findOrFail($id)->delete();
      return true;
    }

}
