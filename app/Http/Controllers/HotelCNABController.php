<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ShippingHotelCNAB240Request;
use App\Services\HotelCNABService as HotelCNABService;

class HotelCNABController extends Controller
{

    private $cnabService;
    public function __construct(HotelCNABService $cnabService)
    {
        $this->cnabService = $cnabService;
    }

    public function cnabParse(Request $request)
    {
        return $this->cnabService->cnabParse($request->all());
    }

    public function return240(Request $request)
    {
        return $this->cnabService->receiveCNAB240($request);
    }
}
