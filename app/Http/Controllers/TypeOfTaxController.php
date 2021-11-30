<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TypeOfTaxService as TypeOfTaxService;

class TypeOfTaxController extends Controller
{
    private $typeOfTaxService;

    public function __construct(TypeOfTaxService $typeOfTaxService)
    {
        $this->typeOfTaxService = $typeOfTaxService;
    }

    public function index(Request $request)
    {
        return $this->typeOfTaxService->getAllTypeOfTax($request->all());
    }

    public function show($id)
    {
        return $this->typeOfTaxService->getTypeOfTax($id);
    }

    public function store(Request $request)
    {
        return $this->typeOfTaxService->postTypeOfTax($request->all());
    }

    public function update(Request $request, $id)
    {
        return $this->typeOfTaxService->putTypeOfTax($id, $request->all());
    }

    public function destroy($id)
    {
        $typeOfTax = $this->typeOfTaxService->deleteTypeOfTax($id);
        return response('');
    }

}
