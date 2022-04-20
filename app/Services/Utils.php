<?php

namespace App\Services;

use App\Models\FormPayment;

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

    public static function search($model,$requestInfo,$excludeFields = null){
        $fillable = $model->getFillable();
        if ($excludeFields != null)
        {
            foreach ($fillable as $key=>$value) {
                if(in_array($fillable[$key], $excludeFields)){
                    unset($fillable[$key]);
                }
            }
        }
        $query = $model->query();
        if(array_key_exists('search', $requestInfo)){

            if (self::validateDate($requestInfo['search'], 'd/m/Y')) {
                $requestInfo['search'] = self::formatDate($requestInfo['search']);
            }
            if(array_key_exists('searchFields', $requestInfo)){
                $query->whereLike($requestInfo['searchFields'], "%{$requestInfo['search']}%");
            }
            else{
                $query->whereLike($fillable, "%{$requestInfo['search']}%");
            }
        }
        return $query;
    }

    public static function groupPayments($paymentRequests, $bankCode){

        //$formPayment = FormPayment::where('bank_code', $bankCode)->get();

        $groupPayment = [];


        foreach($paymentRequests as $paymentRequest)
        {
            $cont = 0;
            foreach($paymentRequest->group_payment->form_payment as $payment_form)
            {
                if($payment_form->bank_code == $bankCode)
                {
                    if($payment_form->group_form_payment_id == 2) //Default PIX group 2
                    {
                        if(array_key_exists('45', $groupPayment))
                        {
                            array_push($groupPayment['45'], [$paymentRequest]);
                            break;
                        } else
                        {
                            $groupPayment = array('45' => [$paymentRequest] );
                            break;
                        }
                    }
                }
            }
        }

        dd($groupPayment);



        $count = 1;
        foreach($groupPayment['45'] as $teste)
        {
            $count += 1;
        }
        dd($count);
    return $groupPayment;

    }
}
