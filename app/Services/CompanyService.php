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
    private $with = ['bank_account', 'managers', 'city'];

    public function __construct(Company $company, BankAccount $bankAccount, CompanyHasBankAccount $companyHasBankAccount)
    {
        $this->company = $company;
        $this->bankAccount = $bankAccount;
        $this->companyHasBankAccount = $companyHasBankAccount;
    }

    public function getAllCompany($requestInfo)
    {
        $company = Utils::search($this->company, $requestInfo);
        return Utils::pagination($company->with($this->with), $requestInfo);
    }

    public function getCompany($id)
    {
        return $this->company->with($this->with)->findOrFail($id);
    }

    public function postCompany($companyInfo)
    {
        $company = new Company;
        $company = $company->create($companyInfo);
        if (array_key_exists('managers', $companyInfo)) {
            $company->managers()->sync($companyInfo['managers']);
        }
        $this->syncBankAccounts($company, $companyInfo);
        return $this->company->with($this->with)->findOrFail($company->id);
    }

    public function putCompany($id, $companyInfo)
    {
        $company = $this->company->findOrFail($id);
        $company->fill($companyInfo)->save();
        if (array_key_exists('managers', $companyInfo)) {
            $company->managers()->sync($companyInfo['managers']);
        }
        $this->putBankAccounts($id, $companyInfo);
        return $this->company->with($this->with)->findOrFail($company->id);
    }

    public function deleteCompany($id)
    {
        $companies = $this->company->with($this->with)->findOrFail($id)->delete();
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
                $syncArray[] = [
                    'bank_account_id' => $bankAccount->id,
                    'default_bank' => $bank['default_bank'] ?? false,
                ];
            }
            $company->bank_account()->sync($syncArray);
        }
    }

    public function putBankAccounts($id, $companyInfo){

        $updateBankAccounts = [];
        $createdBankAccounts = [];

        if(array_key_exists('bank_accounts', $companyInfo)){
            $attachArray = [];

            foreach($companyInfo['bank_accounts'] as $bank){
                if (array_key_exists('id', $bank)){
                    $bankAccount = $this->bankAccount->with('bank_account_default')->findOrFail($bank['id']);
                    $bankAccount->fill($bank)->save();
                    $updateBankAccounts[] = $bank['id'];
                    $companyHasBankAccount = CompanyHasBankAccount::findOrFail($bankAccount->bank_account_default->id);
                    $companyHasBankAccount->fill($bank)->save();
                } else {
                    $bankAccount = new BankAccount;
                    $bankAccount = $bankAccount->create($bank);
                    $attachArray[] = [
                        'bank_account_id' => $bankAccount->id,
                        'default_bank' => $bank['default_bank'] ?? false,
                    ];
                    $createdBankAccounts[] = $bankAccount->id;
                }
            }

            $collection = $this->providerHasBankAccounts
            ->where('provider_id', $id)
            ->whereNotIn('bank_account_id', $updateBankAccounts)
            ->whereNotIn('bank_account_id', $createdBankAccounts)
            ->get(['bank_account_id']);
            $this->bankAccount->destroy($collection->toArray());

            $provider = $this->provider->findOrFail($id);
            $provider->bank_account()->attach($attachArray);
        }
    }
}
