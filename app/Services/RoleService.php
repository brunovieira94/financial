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

    public function getAllRole()
    {
        return $this->role->with('modules')->get();
    }

    public function getRole($id)
    {
        return $this->role->with('modules')->findOrFail($id);
    }

    public function postRole($roleInfo)
    {
        $role = new Role;
        $role = $role->create($roleInfo);
        self::syncModules($role, $roleInfo);        
        return $this->role->with('modules')->findOrFail($role->id);
    }

    public function putRole($id, $roleInfo)
    {
        $role = $this->role->with('modules')->findOrFail($id);
        $role->fill($roleInfo)->save();
        self::syncModules($role, $roleInfo);
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

