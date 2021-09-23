<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CountryService as CountryService;
use App\Http\Requests\StoreCountryRequest;

class CountryController extends Controller
{
    private $countryService;

    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }

    public function index(Request $request)
    {
        return $this->countryService->getAllCountry($request->all());
    }

    public function show($id)
    {
        return $this->countryService->getCountry($id);
    }

    public function store(StoreCountryRequest $request)
    {
        return $this->countryService->postCountry($request->all());
    }

    public function update(StoreCountryRequest $request, $id)
    {
        return $this->countryService->putCountry($id, $request->all());
    }

    public function destroy($id)
    {
        $country = $this->countryService->deleteCountry($id);
        return response('');
    }

}
