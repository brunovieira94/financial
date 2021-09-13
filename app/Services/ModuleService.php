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

    public function postModule($moduleInfo)
    {
        $module = new Module;
        if(array_key_exists('parent', $moduleInfo) && is_numeric($moduleInfo['parent'])){
            $this->module->findOrFail($moduleInfo['parent'])->get();
        }
        return $module->create($moduleInfo);
    }

    public function putModule($id, $moduleInfo)
    {
        $module = $this->module->findOrFail($id);
        if(array_key_exists('parent', $moduleInfo)){
            if(is_numeric($moduleInfo['parent'])){
                $this->module->findOrFail($moduleInfo['parent'])->get();
            }
            if($moduleInfo['parent'] == $id){
                abort(500);
            }
        }
        $module->fill($moduleInfo)->save();
        return $module;
    }

    public function deleteModule($id)
    {
        $module = $this->module->findOrFail($id)->where('id', $id)->get();
        $nestable = $this->module->nestable($module)->toArray();
        $arrayIds = Utils::getDeleteKeys($nestable);
        $this->module->destroy($arrayIds);
        return true;
    }
}

