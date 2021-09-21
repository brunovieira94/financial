<?php

namespace App\Services;

class Utils
{
    public static function getDeleteKeys($nestable){
        $arrayIds = [];
        foreach($nestable as $key=>$value){
            array_push($arrayIds, $nestable[$key]['id']);
            if(sizeof($nestable[$key]['children']) > 0){
                $auxArray = self::getDeleteKeys($nestable[$key]['children']);
                foreach($auxArray as $element){
                    array_push($arrayIds, $element);
                }
            }
        }
        return $arrayIds;
    }

    const defaultPerPage = 20;
    const defaultOrderBy = 'created_at';
    const defaultOrder = 'desc';
}
