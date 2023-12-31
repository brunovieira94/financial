<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingCnabParse;
use Illuminate\Http\Request;
use App\Http\Requests\ShippingItauCNAB240Request;
use App\Services\ItauCNABService as ItauCNABService;

class ItauCNABController extends Controller
{

    private $cnabService;
    public function __construct(ItauCNABService $cnabService)
    {
        $this->cnabService = $cnabService;
    }

    public function shipping240(ShippingItauCNAB240Request $request)
    {
        return $this->cnabService->generateCNAB240Shipping($request->all());
    }

    public function return240(Request $request)
    {
        return $this->cnabService->receiveCNAB240($request);
    }

    public function cnabParse(ShippingCnabParse $request)
    {
        return $this->cnabService->cnabParse($request->all());
    }
}
