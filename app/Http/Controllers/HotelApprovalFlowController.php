<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreHotelApprovalFlowRequest;
use App\Services\HotelApprovalFlowService as HotelApprovalFlowService;

class HotelApprovalFlowController extends Controller
{

    private $hotelApprovalFlowService;

    public function __construct(HotelApprovalFlowService $hotelApprovalFlowService)
    {
        $this->hotelApprovalFlowService = $hotelApprovalFlowService;
    }

    public function index(Request $request)
    {
        return $this->hotelApprovalFlowService->getAllHotelApprovalFlow($request->all());
    }

    public function store(StoreHotelApprovalFlowRequest $request)
    {
        $this->hotelApprovalFlowService->postHotelApprovalFlow($request->all());
        return response('', 201);
    }
}
