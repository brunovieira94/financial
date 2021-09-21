<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StateService as StateService;
use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\PutStateRequest;

class StateController extends Controller
{
    private $stateService;

    public function __construct(StateService $stateService)
    {
        $this->stateService = $stateService;
    }

    public function index(Request $request)
    {
        return $this->stateService->getAllState($request->all());
    }

    public function show($id)
    {
        return $this->stateService->getState($id);
    }

    public function store(StoreStateRequest $request)
    {
        return $this->stateService->postState($request->all());
    }

    public function update(PutStateRequest $request, $id)
    {
        return $this->stateService->putState($id, $request->all());
    }

    public function destroy($id)
    {
        $state = $this->stateService->deleteState($id);
        return response('');
    }

}
