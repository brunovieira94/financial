<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyHasBankAccount extends Model
{
    protected $table='company_has_bank_accounts';
    public $timestamps = false;
    protected $fillable = ['company_id', 'bank_account_id', 'default_bank'];
    protected $hidden = ['company_id'];

    public function bank_account()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_id')->with(['bank']);
    }
}
