<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BankAccountService as BankAccountService;
use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\PutBankAccountRequest;

class BankAccountController extends Controller
{

    private $bankAccount;

    public function __construct(BankAccountService $bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    public function index()
    {
        return $this->bankAccount->getAllBankAccount();
    }

    public function show($id)
    {
        return $this->bankAccount->getBankAccount($id);
    }

    public function store(StoreBankAccountRequest $request)
    {
        $bankAccount = $this->bankAccount->postBankAccount($request->all());
        return response($bankAccount);
    }

    public function update(PutBankAccountRequest $request, $id)
    {
        $bankAccount = $this->bankAccount->putBankAccount($id, $request->all());
        return response($bankAccount);
    }

    public function destroy($id)
    {
        $this->bankAccount->deleteBankAccount($id);
        return response('');
    }
}
