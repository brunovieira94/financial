<?php

namespace App\Services;
use App\Models\Provider;
use App\Models\BankAccount;

class ProviderService
{
    private $provider;
    private $bankAccount;
    public function __construct(Provider $provider, BankAccount $bankAccount)
    {
        $this->provider = $provider;
        $this->bankAccount = $bankAccount;
    }

    public function getAllProvider()
    {
        return $this->provider->with('bankAccount')->get();
    }

    public function getProvider($id)
    {
      return $this->provider->with('bankAccount')->findOrFail($id);
    }

    public function postProvider($providerInfo)
    {
        $provider = new Provider;
        $provider = $provider->create($providerInfo);


        self::syncBankAccounts($provider, $providerInfo);
        return $this->provider->with('bankAccount')->findOrFail($provider->id);
    }

    public function putProvider($id, $providerInfo)
    {
        $provider = $this->provider->findOrFail($id);
        $provider->fill($providerInfo)->save();

        self::putBankAccounts($id, $providerInfo);
        return $this->provider->with('bankAccount')->findOrFail($provider->id);
    }

    public function deleteProvider($id)
    {
        $providers = $this->provider->with('bankAccount')->findOrFail($id)->delete();
        return true;
    }

    public function putBankAccounts($id, $providerInfo){

        if(array_key_exists('bank_accounts', $providerInfo)){
            foreach($providerInfo['bank_accounts'] as $bank){
                if (array_key_exists('id', $bank)){
                    $bankAccount = $this->bankAccount->findOrFail($bank['id']);
                    $bankAccount->fill($bank)->save();
                    $updatedOrCreatedBankAccounts[] = $bank['id'];
                } else {
                    $bankAccount = new BankAccount;
                    $bankAccount = $bankAccount->create($bank);
                    $updatedOrCreatedBankAccounts[] = $bankAccount->id;
                }
            }

            $provider = $this->provider->findOrFail($id);
            $provider->bankAccount()->sync($updatedOrCreatedBankAccounts);
        }
    }

    public function syncBankAccounts($provider, $providerInfo){
        $syncArray = [];
        if(array_key_exists('bank_accounts', $providerInfo)){
            foreach($providerInfo['bank_accounts'] as $bank){
                $bankAccount = new BankAccount;
                $bankAccount = $bankAccount->create($bank);
                $syncArray[] = $bankAccount->id;
            }
            $provider->bankAccount()->sync($syncArray);
        }
    }

}
