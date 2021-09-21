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
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->bankAccount->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function getBankAccount($id)
    {
        return $this->bankAccount->findOrFail($id);
    }

    public function postBankAccount($bankAccountInfo)
    {
        $bankAccount = new BankAccount;
        return $bankAccount->create($bankAccountInfo);
    }

    public function putBankAccount($id, $bankAccountInfo)
    {
        $bankAccount = $this->bankAccount->findOrFail($id);
        $bankAccount->fill($bankAccountInfo)->save();
        return $bankAccount;
    }

    public function deleteBankAccount($id)
    {
      $this->bankAccount->findOrFail($id)->delete();
      return true;
    }

}
