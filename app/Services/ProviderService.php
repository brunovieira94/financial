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
        $provider = Utils::search($this->provider, $requestInfo);
        return Utils::pagination($provider->with($this->with), $requestInfo);
    }

    public function getProvider($id)
    {
        return $this->provider->with($this->with)->findOrFail($id);
    }

    public function postProvider($providerInfo)
    {
        $provider = new Provider;
        if (!array_key_exists('trade_name', $providerInfo)) {
            $providerInfo['trade_name'] = $providerInfo['full_name'];
        }
        $provider = $provider->create($providerInfo);

        $this->syncBankAccounts($provider, $providerInfo);
        return $this->provider->with($this->with)->findOrFail($provider->id);
    }

    public function putProvider($id, $providerInfo)
    {
        $provider = $this->provider->findOrFail($id);
        if ($provider->provider_type == 'F') {
            $providerInfo['trade_name'] = $providerInfo['full_name'];
        }

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

    public function putBankAccounts($id, $providerInfo)
    {

        $updateBankAccounts = [];
        $createdBankAccounts = [];

        $genericProvider = array_key_exists('generic_provider', $providerInfo) && $providerInfo['generic_provider'];

        if (array_key_exists('bank_accounts', $providerInfo)) {
            $attachArray = [];

            foreach ($providerInfo['bank_accounts'] as $bank) {
                $bank['hidden'] = $genericProvider;
                if (array_key_exists('id', $bank)) {
                    // $bankAccount = $this->bankAccount->with('bank_account_default')->findOrFail($bank['id']);
                    $bankAccount = $this->bankAccount->findOrFail($bank['id']);
                    $bankAccount->fill($bank)->save();
                    $updateBankAccounts[] = $bank['id'];
                    //$providerHasBankAccount = ProviderHasBankAccounts::findOrFail($bankAccount->bank_account_default->id);
                    //$providerHasBankAccount->fill($bank)->save();

                    if (!ProviderHasBankAccounts::where('provider_id', $id)->where('bank_account_id', $bankAccount->id)->exists()) {
                        $attachArray[] = [
                            'bank_account_id' => $bankAccount->id,
                            'default_bank' => $bank['default_bank'] ?? false,
                        ];
                    }
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

            // Execute delete procedure only if not a generic provider
            if (!$genericProvider) {
                $collection = $this->providerHasBankAccounts->with('bank_account')
                    ->where('provider_id', $id)
                    ->whereNotIn('bank_account_id', $updateBankAccounts)
                    ->whereNotIn('bank_account_id', $createdBankAccounts)
                    ->whereRelation('bank_account', 'hidden', '=', false)
                    ->get(['bank_account_id']);
                $this->bankAccount->destroy($collection->pluck('bank_account_id'));
            }

            $provider = $this->provider->findOrFail($id);
            $provider->bank_account()->attach($attachArray);
        }

        $updateBankAccounts = $this->providerHasBankAccounts::where('provider_id', $id)->get(['bank_account_id']);
        BankAccount::whereIn('id', $updateBankAccounts->toArray())->update(['hidden' => $genericProvider]);
    }

    public function syncBankAccounts($provider, $providerInfo)
    {
        $syncArray = [];
        if (array_key_exists('bank_accounts', $providerInfo)) {
            $genericProvider = array_key_exists('generic_provider', $providerInfo) && $providerInfo['generic_provider'];
            foreach ($providerInfo['bank_accounts'] as $bank) {
                $bankAccount = new BankAccount;
                $bank['hidden'] = $genericProvider;
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
