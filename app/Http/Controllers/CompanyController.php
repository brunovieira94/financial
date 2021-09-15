<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompanyService as CompanyService;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\PutCompanyRequest;

class CompanyController extends Controller
{
    private $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function index()
    {
        return $this->companyService->getAllCompany();
    }

    public function show($id)
    {
        return $this->companyService->getCompany($id);
    }

    public function store(StoreCompanyRequest $request)
    {
        return $this->companyService->postCompany($request->all());
    }

    public function update(PutCompanyRequest $request, $id)
    {
        return $this->companyService->putCompany($id, $request->all());
    }

    public function destroy($id)
    {
        $company = $this->companyService->deleteCompany($id);
        return response('');
    }
}
