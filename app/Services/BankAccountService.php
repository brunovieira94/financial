<?php

namespace App\Services;
use App\Models\BankAccount;

class BankService
{
    private $bankAccount;

    public function __construct(BankAccount $bankAccount){
        $this->bankAccount = $bankAccount;
    }

    public function getAllBankAccount()
    {

    }

    public function getBankAccount($id)
    {

    }

    public function postBankAccount($titleBank)
    {

    }

    public function putBankAccount($id, $titleBank)
    {

    }

    public function deleteBankAccount($id)
    {
      return true;
    }

}
