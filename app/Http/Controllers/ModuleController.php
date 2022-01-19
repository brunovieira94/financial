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
}
