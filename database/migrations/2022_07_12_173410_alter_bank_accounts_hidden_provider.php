<?php

use App\Models\BankAccount;
use App\Models\Provider;
use App\Models\ProviderHasBankAccounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBankAccountsHiddenProvider extends Migration
{
    public function up()
    {
        if(Provider::where('generic_provider', true)->exists()){
            $providers = Provider::where('generic_provider', true)->get();
            foreach($providers as $provider){
                $providerHasBankAccount = ProviderHasBankAccounts::where('provider_id', $provider->id)->get('bank_account_id');
                BankAccount::whereIn('id', $providerHasBankAccount->pluck('bank_account_id'))->update(['hidden' => true]);
            }
        }
    }
}
