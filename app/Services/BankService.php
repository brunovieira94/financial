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
