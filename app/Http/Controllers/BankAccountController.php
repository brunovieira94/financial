<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BankAccountService as BankAccountService;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\PutBankAccountRequest;

class BankAccountController extends Controller
{

    private $bankAccountService;

    public function __construct(BankAccountService $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    public function index()
    {
        return $this->bankAccountService->getAllBankAccount();
    }

    public function show($id)
    {
        return $this->bankAccountService->getBankAccount($id);
    }

    public function store(StoreBankAccountRequest $request)
    {
        return  $this->bankAccountService->postBankAccount($request->all());
    }

    public function update(PutBankAccountRequest $request, $id)
    {
        return $this->bankAccountService->putBankAccount($id, $request->all());
    }

    public function destroy($id)
    {
        $this->bankAccountService->deleteBankAccount($id);
        return response('');
    }
}
