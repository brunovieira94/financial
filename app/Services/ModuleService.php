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
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        $modules = $this->module->where('parent', null)->orderBy($orderBy, $order)->paginate($perPage);
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

