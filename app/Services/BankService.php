<?php

namespace App\Services;
use App\Models\Bank;

class BankService
{
    private $bank;
<<<<<<< HEAD
=======

>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
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

<<<<<<< HEAD
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
=======
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
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function deleteBank($id)
    {
      $this->bank->findOrFail($id)->delete();
      return true;
    }

}
