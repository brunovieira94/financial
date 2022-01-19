<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\CitiesImport;
use App\Services\CityService as CityService;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\PutCityRequest;

class CityController extends Controller
{
    private $cityService;

    public function __construct(CityService $cityService)
    {
        $this->cityService = $cityService;
    }

    public function index(Request $request)
    {
        return $this->cityService->getAllCity($request->all());
    }

    public function show($id)
    {
        return $this->cityService->getCity($id);
    }

    public function store(StoreCityRequest $request)
    {
        return $this->cityService->postCity($request->all());
    }

    public function update(PutCityRequest $request, $id)
    {
        return $this->cityService->putCity($id, $request->all());
    }

    public function destroy($id)
    {
        $state = $this->cityService->deleteCity($id);
        return response('');
    }

    public function import()
    {
        (new CitiesImport)->import(request()->file('import_file'));
        return response('');
    }

}
