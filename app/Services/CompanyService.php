<?php

namespace App\Services;
use App\Models\Company;
use App\Models\BankAccount;
use App\Models\CompanyHasBankAccount;

class CompanyService
{
    private $company;
    private $bankAccount;
    private $companyHasBankAccount;
    public function __construct(Company $company, BankAccount $bankAccount, CompanyHasBankAccount $companyHasBankAccount)
    {
        $this->company = $company;
        $this->bankAccount = $bankAccount;
        $this->companyHasBankAccount = $companyHasBankAccount;
    }

    public function getAllCompany($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->company->with('bankAccount')->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function getCompany($id)
    {
      return $this->company->with('bankAccount')->findOrFail($id);
    }

    public function postCompany($companyInfo)
    {
        $company = new Company;
        $company = $company->create($companyInfo);

        self::syncBankAccounts($company, $companyInfo);
        return $this->company->with('bankAccount')->findOrFail($company->id);
    }

    public function putCompany($id, $companyInfo)
    {
        $company = $this->company->findOrFail($id);
        $company->fill($companyInfo)->save();

        self::putBankAccounts($id, $companyInfo);
        return $this->company->with('bankAccount')->findOrFail($company->id);
    }

    public function deleteCompany($id)
    {
        $companies = $this->company->with('bankAccount')->findOrFail($id)->delete();
        $collection = $this->companyHasBankAccount->where('company_id', $id)->get(['bank_account_id']);
        $this->bankAccount->destroy($collection->toArray());
        return true;
    }

    public function syncBankAccounts($company, $companyInfo){
        $syncArray = [];
        if(array_key_exists('bank_accounts', $companyInfo)){
            foreach($companyInfo['bank_accounts'] as $bank){
                $bankAccount = new BankAccount;
                $bankAccount = $bankAccount->create($bank);
                $syncArray[] = $bankAccount->id;
            }
            $company->bankAccount()->sync($syncArray);
        }
    }

    public function putBankAccounts($id, $companyInfo){

        $updateBankAccounts = [];
        $createdBankAccounts = [];

        if(array_key_exists('bank_accounts', $companyInfo)){
            foreach($companyInfo['bank_accounts'] as $bank){
                if (array_key_exists('id', $bank)){
                    $bankAccount = $this->bankAccount->findOrFail($bank['id']);
                    $bankAccount->fill($bank)->save();
                    $updateBankAccounts[] = $bank['id'];
                } else {
                    $bankAccount = new BankAccount;
                    $bankAccount = $bankAccount->create($bank);
                    $createdBankAccounts[] = $bankAccount->id;
                }
            }
            $collection = $this->companyHasBankAccount->where('company_id', $id)->whereNotIn('bank_account_id', $updateBankAccounts)->whereNotIn('bank_account_id', $createdBankAccounts)->get(['bank_account_id']);

            $this->bankAccount->destroy($collection->toArray());

            $company = $this->company->findOrFail($id);
            $company->bankAccount()->attach($createdBankAccounts);
        }
    }

}
