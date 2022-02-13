<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderHasBankAccounts extends Model
{
    protected $table='provider_has_bank_accounts';
    public $timestamps = false;
    protected $fillable = ['provider_id', 'bank_account_id', 'default_bank'];
    protected $hidden = ['provider_id'];

    public function bank_account()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_id')->with('bank');
    }

}
