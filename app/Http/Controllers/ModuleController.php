<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Requests\PutModuleRequest;
use App\Services\ModuleService as ModuleService;

class ModuleController extends Controller
{

    private $moduleService;

    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    public function index(Request $request)
    {
        return $this->moduleService->getAllModule($request->all());
    }

    public function show($id)
    {
        return $this->moduleService->getModule($id);
    }

    public function store(StoreModuleRequest $request)
    {
        $module = $this->moduleService->postModule($request->all());
        return response($module, 201);
    }

    public function update(PutModuleRequest $request, $id)
    {
        return $this->moduleService->putModule($id, $request->all());
    }

    public function destroy($id)
    {
        $module = $this->moduleService->deleteModule($id);
        return response('');
    }
}
