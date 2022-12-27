<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlutoTableStateRequest;
use Illuminate\Http\Request;

use App\Services\PlutoTableStateService;

class PlutoTableStateController extends Controller
{
    private $plutoTableStateService;

    public function __construct(PlutoTableStateService $plutoTableStateService)
    {
        $this->plutoTableStateService = $plutoTableStateService;
    }

    public function saveState(PlutoTableStateRequest $request)
    {
        $request['user_id'] = auth()->user()->id;
        return $this->plutoTableStateService->saveState($request->all());
    }

    public function getState(PlutoTableStateRequest $request)
    {
        $request['user_id'] = auth()->user()->id;
        return $this->plutoTableStateService->getState($request->all());
    }
}
