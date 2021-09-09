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

    public function getAllModule()
    {
        return $this->module->get();
    }

    public function getModule($id)
    {
        return $this->module->findOrFail($id);
    }

    public function postModule($moduleInfo)
    {
        $module = new Module;
        return $module->create($moduleInfo);    
    }

    public function putModule($id, $moduleInfo)
    {
        $module = $this->module->findOrFail($id);
        $module->fill($moduleInfo)->save();
        return $module;
    }

    public function deleteModule($id)
    {
        $this->module->findOrFail($id)->delete();
        return true;
    }
}

