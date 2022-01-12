<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CompanyService as CompanyService;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\PutCompanyRequest;
use App\Imports\CompaniesImport;

class CompanyController extends Controller
{
    private $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function index(Request $request)
    {
        return $this->companyService->getAllCompany($request->all());
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

    public function import()
    {
        (new CompaniesImport)->import(request()->file('import_file'));
        return response('');
    }
}
