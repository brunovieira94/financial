<?php

namespace App\Services;
use App\Models\Bank;

class BankService
{
    public function getAllBank()
    {
      $bank = Bank::get();
      return $bank;
    }

    public function getBank($id)
    {
      $bank = Bank::findOrFail($id);
      return $bank;
    }

    public function postBank($titleBank)
    {
      $bank = new Bank;
      $bank->title = $titleBank;
      $bank->save();
      return $bank;
    }

    public function putBank($id, $titleBank)
    {
      $bank = Bank::findOrFail($id);
      $bank->title = $titlePaymentType;
      $bank->save();
      return $bank;
    }

    public function deleteBank($id)
    {
      Bank::findOrFail($id)->delete();
      return true;
    }

}
