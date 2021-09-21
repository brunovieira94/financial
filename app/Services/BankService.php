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
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->bank->orderBy($orderBy, $order)->paginate($perPage);
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
      $collection = $this->bankAccount->where('bank_id', $id)->get(['id']);
      $this->bankAccount->destroy($collection->toArray());
      $this->bank->findOrFail($id)->delete();
      return true;
    }

}
