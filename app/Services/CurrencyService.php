<?php

namespace App\Services;
use App\Models\Currency;

class CurrencyService
{

    private $currency;
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function getAllCurrency($requestInfo)
    {
        // $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        // $order = $requestInfo['order'] ?? Utils::defaultOrder;
        // $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        $currency = Utils::search($this->currency,$requestInfo);
        return Utils::pagination($currency,$requestInfo);
        //return $currency->orderBy($orderBy, $order)->paginate($perPage);
        //return $this->currency->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function getCurrency($id)
    {
        return $this->currency->findOrFail($id);
    }

    public function postCurrency($currencyInfo)
    {
        $currency = new Currency;
        return $currency->create($currencyInfo);
    }

    public function putCurrency($id, $currencyInfo)
    {
        $currency = $this->currency->findOrFail($id);
        $currency->fill($currencyInfo)->save();
        return $currency;
    }

    public function deleteCurrency($id)
    {
        $this->currency->findOrFail($id)->delete();
        return true;
    }

}

