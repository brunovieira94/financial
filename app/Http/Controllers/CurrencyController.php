<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreCurrencyRequest;
use App\Services\CurrencyService as CurrencyService;

class CurrencyController extends Controller
{

    private $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        return $this->currencyService->getAllCurrency();
    }

    public function show($id)
    {
        return $this->currencyService->getCurrency($id);
    }

    public function store(StoreCurrencyRequest $request)
    {
        $currency = $this->currencyService->postCurrency($request->all());
        return response($currency, 201);
    }

    public function update(StoreCurrencyRequest $request, $id)
    {
        return $this->currencyService->putCurrency($id, $request->all());
    }

    public function destroy($id)
    {
        $currency = $this->currencyService->deleteCurrency($id);
        return response('');
    }
}
