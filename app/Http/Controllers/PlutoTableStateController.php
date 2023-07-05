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
        $requestInfo = $request->all();

        if (!array_key_exists('user_id', $requestInfo)) {
            $requestInfo['user_id'] = auth()->user()->id;
        }

        return $this->plutoTableStateService->saveState($requestInfo);
    }

    public function getState(PlutoTableStateRequest $request)
    {
        $requestInfo = $request->all();

        if (!array_key_exists('user_id', $requestInfo)) {
            $requestInfo['user_id'] = auth()->user()->id;
        }

        return $this->plutoTableStateService->getState($requestInfo);
    }
}
