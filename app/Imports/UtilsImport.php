<?php

namespace App\Imports;

class UtilsImport {

    public static function getLastedID ($codeText, $model){
        $parent = null;
        $codes = explode(',', $codeText);
        $auxiliary = null;

        foreach ($codes as $code){
            $auxiliary = $model::where('code', $code)->where('parent', $parent)->first();
            if($auxiliary == null){
                break;
            }
            $parent = $auxiliary->id;
        }
        if($auxiliary == null) {
            return null;
        } else{
            return $auxiliary->id;
        }
    }


}
