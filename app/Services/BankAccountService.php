<?php

namespace App\Services;
use App\Models\BankAccount;

class BankAccountService
{
    private $bankAccount;

    public function __construct(BankAccount $bankAccount){
        $this->bankAccount = $bankAccount;
    }

    public function getAllBankAccount($requestInfo)
    {
        $bankAccount = Utils::search($this->bankAccount,$requestInfo);
        return Utils::pagination($bankAccount->with('bank'), $requestInfo);
    }

    public function getBankAccount($id)
    {
        return $this->bankAccount->with('bank')->findOrFail($id);
    }

    public function postBankAccount($bankAccountInfo)
    {
        $bankAccount = new BankAccount;
        $bankAccount = $bankAccount->create($bankAccountInfo);
        return $this->bankAccount->with('bank')->findOrFail($bankAccount->id);
    }

    public function putBankAccount($id, $bankAccountInfo)
    {
        $bankAccount = $this->bankAccount->findOrFail($id);
        $bankAccount->fill($bankAccountInfo)->save();
        return $this->bankAccount->with('bank')->findOrFail($id);
    }

    public function deleteBankAccount($id)
    {
      $this->bankAccount->findOrFail($id)->delete();
      return true;
    }

}
