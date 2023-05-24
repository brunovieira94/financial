<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\BankAccount;
use App\Models\ProviderHasAttachments;
use App\Models\ProviderHasBankAccounts;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use Illuminate\Http\Request;

class ProviderService
{
    private $provider;
    private $bankAccount;
    private $providerHasBankAccounts;
    private $attachments;
    private $with = ['bank_account', 'provider_category', 'user', 'chart_of_account', 'cost_center', 'city', 'attachments'];
    public function __construct(Provider $provider, BankAccount $bankAccount, ProviderHasBankAccounts $providerHasBankAccounts, ProviderHasAttachments $attachments)
    {
        $this->provider = $provider;
        $this->bankAccount = $bankAccount;
        $this->providerHasBankAccounts = $providerHasBankAccounts;
        $this->attachments = $attachments;
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

    public function postProvider(Request $request)
    {
        $providerInfo = $request->all();
        $provider = new Provider;
        if (!array_key_exists('trade_name', $providerInfo)) {
            $providerInfo['trade_name'] = $providerInfo['full_name'];
        }
        $provider = $provider->create($providerInfo);

        $this->syncBankAccounts($provider, $providerInfo);
        $this->syncAttachments($provider, $providerInfo, $request);
        return $this->provider->with($this->with)->findOrFail($provider->id);
    }

    public function putProvider($id, Request $request)
    {
        $providerInfo = $request->all();
        $provider = $this->provider->findOrFail($id);
        if ($provider->provider_type == 'F') {
            $providerInfo['trade_name'] = $providerInfo['full_name'];
        }

        $provider->fill($providerInfo)->save();

        $this->putBankAccounts($id, $providerInfo);
        $this->putAttachments($id, $providerInfo, $request);
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

    public function syncAttachments($provider, $providerInfo, Request $request)
    {
        if (array_key_exists('attachments', $providerInfo)) {
            foreach ($providerInfo['attachments'] as $key => $attachment) {
                $providerHasAttachments = new ProviderHasAttachments;
                $attachment['attachment'] = $this->storeAttachment($request, $key);
                $providerHasAttachments = $providerHasAttachments->create([
                    'provider_id' => $provider->id,
                    'attachment' => $attachment['attachment'],
                ]);
            }
        }
    }

       public function putAttachments($id, $providerInfo, Request $request)
    {

        $updateAttachments = [];
        $createdAttachments = [];

        if (array_key_exists('attachments', $providerInfo)) {
            foreach ($providerInfo['attachments'] as $key => $attachment) {
                if (array_key_exists('id', $attachment)) {
                    $updateAttachments[] = $attachment['id'];
                } else {
                    $providerHasAttachments = new ProviderHasAttachments;
                    $attachment['attachment'] = $this->storeAttachment($request, $key);
                    $providerHasAttachments = $providerHasAttachments->create([
                        'provider_id' => $id,
                        'attachment' => $attachment['attachment'],
                    ]);
                    $createdAttachments[] = $providerHasAttachments->id;
                }
            }
        }
        $this->attachments->where('provider_id', $id)->whereNotIn('id', $updateAttachments)->whereNotIn('id', $createdAttachments)->delete();
    }

    public function storeAttachment(Request $request, $key)
    {
        $data = uniqid(date('HisYmd'));

        if ($request->hasFile('attachments.' . $key . '.attachment') && $request->file('attachments.' . $key . '.attachment')->isValid()) {
            $extensionAttachment = $request['attachments.' . $key . '.attachment']->extension();
            $originalNameAttachment  = explode('.', $request['attachments.' . $key . '.attachment']->getClientOriginalName());
            $nameFileAttachment = "{$originalNameAttachment[0]}_{$data}.{$extensionAttachment}";
            $uploadAttachment = $request['attachments.' . $key . '.attachment']->storeAs('attachment', $nameFileAttachment);

            if (!$uploadAttachment) {
                return response('Falha ao realizar o upload do arquivo.', 500)->send();
            }
            return $nameFileAttachment;
        }
    }
}
