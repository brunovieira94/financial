<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BusinessService as BusinessService;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\PutBusinessRequest;

class BusinessController extends Controller
{
    private $businessService;

    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    public function index(Request $request)
    {
        return $this->businessService->getAllBusiness($request->all());
    }

    public function show($id)
    {
        return $this->businessService->getBusiness($id);
    }

    public function store(StoreBusinessRequest $request)
    {
        return $this->businessService->postBusiness($request->all());
    }

    public function update(PutBusinessRequest $request, $id)
    {
        return $this->businessService->putBusiness($id, $request->all());
    }

    public function destroy($id)
    {
        $business = $this->businessService->deleteBusiness($id);
        return response('');
    }

}
