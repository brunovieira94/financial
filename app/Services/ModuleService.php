<?php

namespace App\Services;
use App\Models\Module;

class ModuleService
{

    private $module;
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function getAllModule($requestInfo)
    {
        $modules = $this->module->where('parent', null)->get();
        $nestable = $this->module->nestable($modules);
        return $nestable;
    }

    public function getModule($id)
    {
        $module = $this->module->findOrFail($id)->where('id', $id)->get();
        $nestable = $this->module->nestable($module);
        return $nestable;
    }
}

