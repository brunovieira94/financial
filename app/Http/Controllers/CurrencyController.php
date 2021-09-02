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
        $currency = $this->currencyService->getAllCurrency();
        return response($currency);
    }

    public function show($id)
    {
        $currency = $this->currencyService->getCurrency($id);
        return response($currency);
    }

    public function store(StoreCurrencyRequest $request)
    {
        $currency = $this->currencyService->postCurrency($request->title);
        return response($currency, 201);
    }

    public function update(StoreCurrencyRequest $request, $id)
    {
        $currency = $this->currencyService->putCurrency($id, $request->title);
        return response($currency);
    }

    public function destroy($id)
    {
        $currency = $this->currencyService->deleteCurrency($id);
        return response('');
    }
}