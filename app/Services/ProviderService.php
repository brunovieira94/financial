<?php

namespace App\Services;
use App\Models\Provider;
use App\Models\BankAccount;
use App\Models\ProviderHasBankAccounts;

class ProviderService
{
    private $provider;
    private $bankAccount;
    private $providerHasBankAccounts;
    private $with = ['bank_account', 'provider_category', 'user', 'chart_of_account', 'cost_center', 'city'];
    public function __construct(Provider $provider, BankAccount $bankAccount, ProviderHasBankAccounts $providerHasBankAccounts)
    {
        $this->provider = $provider;
        $this->bankAccount = $bankAccount;
        $this->providerHasBankAccounts = $providerHasBankAccounts;
    }

    public function getAllProvider($requestInfo)
    {
        $provider = Utils::search($this->provider,$requestInfo);
        return Utils::pagination($provider->with($this->with),$requestInfo);
    }

    public function getProvider($id)
    {
      return $this->provider->with($this->with)->findOrFail($id);
    }

    public function postProvider($userId, $providerInfo)
    {
        $provider = new Provider;
        $providerInfo['user_id'] = $userId;
        $provider = $provider->create($providerInfo);

        $this->syncBankAccounts($provider, $providerInfo);
        return $this->provider->with($this->with)->findOrFail($provider->id);
    }

    public function putProvider($id, $providerInfo)
    {
        $provider = $this->provider->findOrFail($id);
        $provider->fill($providerInfo)->save();

        $this->putBankAccounts($id, $providerInfo);
        return $this->provider->with($this->with)->findOrFail($provider->id);
    }

    public function deleteProvider($id)
    {
        $providers = $this->provider->findOrFail($id)->delete();
        $collection = $this->providerHasBankAccounts->where('provider_id', $id)->get(['bank_account_id']);
        $this->bankAccount->destroy($collection->toArray());
        return true;
    }

    public function putBankAccounts($id, $providerInfo){

        $updateBankAccounts = [];
        $createdBankAccounts = [];

        if(array_key_exists('bank_accounts', $providerInfo)){
            foreach($providerInfo['bank_accounts'] as $bank){
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

            $collection = $this->providerHasBankAccounts->where('provider_id', $id)->whereNotIn('bank_account_id', $updateBankAccounts)->whereNotIn('bank_account_id', $createdBankAccounts)->get(['bank_account_id']);
            $this->bankAccount->destroy($collection->toArray());

            $provider = $this->provider->findOrFail($id);
            $provider->bank_account()->attach($createdBankAccounts);
        }
    }

    public function syncBankAccounts($provider, $providerInfo){
        $syncArray = [];
        if(array_key_exists('bank_accounts', $providerInfo)){
            foreach($providerInfo['bank_accounts'] as $bank){
                $bankAccount = new BankAccount;
                $bankAccount = $bankAccount->create($bank);
                $syncArray[] = [
                    'bank_account_id' => $bankAccount->id,
                    'default_bank' => $bank['default_bank'] ?? false,
                ];
            }
            $provider->bank_account()->sync($syncArray);
        }
    }

}
