<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BankAccountService;

class BankAccountController extends Controller
{

    private $bankAccount;

    public function __construct(PaymentTypeService $bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    public function index()
    {
        return $this->bankAccount->getAllBankAccount();
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
