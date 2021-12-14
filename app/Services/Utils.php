<?php

namespace App\Services;

class Utils
{
    const defaultPerPage = 20;
    const defaultOrderBy = 'id';
    const defaultOrder = 'desc';

    public static function pagination($model,$requestInfo){
        $orderBy = $requestInfo['orderBy'] ?? self::defaultOrderBy;
        $order = $requestInfo['order'] ?? self::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? self::defaultPerPage;
        return $model->orderBy($orderBy, $order)->paginate($perPage);
    }

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

    public static function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function formatDate($date)
    {
        $date = explode('/', $date);
        $year = $date[2];
        $date[2] = $date[0];
        $date[0] = $year;
        return $date = implode('-', $date);
    }

    public static function search($model,$requestInfo){
        $query = $model->query();
        if(array_key_exists('search', $requestInfo)){

            if (self::validateDate($requestInfo['search'], 'd/m/Y')) {
                $requestInfo['search'] = self::formatDate($requestInfo['search']);
            }
            if(array_key_exists('searchFields', $requestInfo)){
                $query->whereLike($requestInfo['searchFields'], "%{$requestInfo['search']}%");
            }
            else{
                $query->whereLike($model->getFillable(), "%{$requestInfo['search']}%");
            }
        }
        return $query;
    }
}
