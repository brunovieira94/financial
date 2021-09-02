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

    public function getAllCurrency()
    {
        return $this->currency->get();
    }

    public function getCurrency($id)
    {
        return $this->currency->findOrFail($id);
    }

    public function postCurrency($titleCurrency)
    {
        $currency = new Currency;
        $currency->title = $titleCurrency;
        $currency->save();
        return $currency;
    }

    public function putCurrency($id, $titleCurrency)
    {
        $currency = $this->currency->findOrFail($id);
        $currency->title = $titleCurrency;
        $currency->save();
        return $currency;
    }

    public function deleteCurrency($id)
    {
        $this->currency->findOrFail($id)->delete();
        return true;
    }

}

