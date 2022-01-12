<?php

namespace App\Http\Controllers;

use App\Imports\BanksImport;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBankRequest;
use App\Services\BankService as BankService;
use App\Http\Requests\PutBankRequest;

class BankController extends Controller
{
    private $bankService;
    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

    public function index(Request $request)
    {
        return $this->bankService->getAllBank($request->all());
    }

    public function show($id)
    {
        return $this->bankService->getBank($id);
    }

    public function store(StoreBankRequest $request)
    {
        return $this->bankService->postBank($request->all());
    }

    public function update(PutBankRequest $request, $id)
    {
        return $this->bankService->putBank($id, $request->all());
    }

    public function destroy($id)
    {
       $bank = $this->bankService->deleteBank($id);
       return response('');
    }

    public function import()
    {
        (new BanksImport)->import(request()->file('import_file'));
        return response('');
    }
}
