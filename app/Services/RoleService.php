<?php

namespace App\Services;
use App\Models\Role;

class RoleService
{

    private $role;
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    public function getAllRole($requestInfo)
    {
        // $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        // $order = $requestInfo['order'] ?? Utils::defaultOrder;
        // $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        $role = Utils::search($this->role,$requestInfo);
        return Utils::pagination($role->with('modules'),$requestInfo);
        //return $role->with('modules')->orderBy($orderBy, $order)->paginate($perPage);
        //return $this->role->with('modules')->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function getRole($id)
    {
        return $this->role->with('modules')->findOrFail($id);
    }

    public function postRole($roleInfo)
    {
        $role = new Role;
        $role = $role->create($roleInfo);
        $this->syncModules($role, $roleInfo);
        return $this->role->with('modules')->findOrFail($role->id);
    }

    public function putRole($id, $roleInfo)
    {
        $role = $this->role->with('modules')->findOrFail($id);
        $role->fill($roleInfo)->save();
        $this->syncModules($role, $roleInfo);
        return $this->role->with('modules')->findOrFail($id);
    }

    public function deleteRole($id)
    {
        $this->role->findOrFail($id)->delete();
        return true;
    }

    public function syncModules($role, $roleInfo){
        $syncArray = [];
        $crudArray = ['create','read','update','delete','import','export'];
        if(array_key_exists('modules', $roleInfo)){
            foreach($roleInfo['modules'] as $module){
                $syncArray[$module['id']] = [];
                foreach($crudArray as $value){
                    $syncArray[$module['id']][$value] = 0;
                    if(array_key_exists($value, $module)){
                        $syncArray[$module['id']][$value] = $module[$value];
                    }
                }
            }
            $role->modules()->sync($syncArray);
        }
    }

}

