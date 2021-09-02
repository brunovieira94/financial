<?php

namespace App\Services;
use App\Models\Bank;

class BankService
{
    private $bank;

    public function __construct(Bank $bank){
        $this->bank = $bank;
    }

    public function getAllBank()
    {
      return $this->bank->get();
    }

    public function getBank($id)
    {
      return $this->bank->findOrFail($id);
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
      $bank = $this->bank->findOrFail($id);
      $bank->title = $titlePaymentType;
      $bank->save();
      return $bank;
    }

    public function deleteBank($id)
    {
      $this->bank->findOrFail($id)->delete();
      return true;
    }

}
