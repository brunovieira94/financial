<?php

namespace App\Services;
use App\Models\Bank;
use App\Models\BankAccount;

class BankService
{
    private $bank;
    private $bankAccount;

    public function __construct(Bank $bank, BankAccount $bankAccount){
        $this->bank = $bank;
        $this->bankAccount = $bankAccount;
    }

    public function getAllBank($requestInfo)
    {
        $bank = Utils::search($this->bank,$requestInfo);
        return Utils::pagination($bank,$requestInfo);
    }

    public function getBank($id)
    {
      return $this->bank->findOrFail($id);
    }

    public function postBank($bankInfo)
    {
       $bank = new Bank;
       return $bank->create($bankInfo);
    }

    public function putBank($id, $bankInfo)
    {
        $bank = $this->bank->findOrFail($id);
        $bank->fill($bankInfo)->save();
        return $bank;
    }

    public function deleteBank($id)
    {
      $this->bank->findOrFail($id)->delete();
      return true;
    }
}
