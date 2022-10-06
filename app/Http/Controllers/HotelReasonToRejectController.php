<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HotelReasonToRejectService;
use App\Http\Requests\StoreHotelReasonToRejectRequest;

class HotelReasonToRejectController extends Controller
{
    private $hotelReasonToRejectService;
    public function __construct(HotelReasonToRejectService $hotelReasonToRejectService)
    {
        $this->hotelReasonToRejectService = $hotelReasonToRejectService;
    }

    public function index(Request $request)
    {
        return $this->hotelReasonToRejectService->getAllHotelReasonToReject($request->all());
    }

    public function show($id)
    {
        return $this->hotelReasonToRejectService->getHotelReasonToReject($id);
    }

    public function store(StoreHotelReasonToRejectRequest $request)
    {
        return $this->hotelReasonToRejectService->postHotelReasonToReject($request->all());
    }

    public function update(StoreHotelReasonToRejectRequest $request, $id)
    {
        return $this->hotelReasonToRejectService->putHotelReasonToReject($id, $request->all());
    }

    public function destroy($id)
    {
       $bank = $this->hotelReasonToRejectService->deleteHotelReasonToReject($id);
       return response('');
    }
}
