<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreShippingRequest;
use App\Services\ItauCNABService as ItauCNABService;

class ItauCNABController extends Controller
{

    private $cnabService;
    public function __construct(ItauCNABService $cnabService)
    {
        $this->cnabService = $cnabService;
    }

    public function shipping240(StoreShippingRequest $request)
    {
        return $this->cnabService->generateCNAB240Shipping($request->all());
    }

    public function return240(Request $request)
    {
        return $this->cnabService->receiveCNAB240($request);
    }

    public function shipping400(StoreShippingRequest $request)
    {
        return $this->cnabService->generateCNAB400Shipping($request->all());
    }

    public function return400(Request $request)
    {
        return $this->cnabService->receiveCNAB400($request);
    }

}
