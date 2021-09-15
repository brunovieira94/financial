<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;
    protected $table='companies';
    protected $fillable = ['company_name', 'trade_name', 'cnpj'];

    public function bankAccount()
    {
        return $this->belongsToMany(BankAccount::class, 'company_has_bank_accounts', 'company_id', 'bank_account_id');
    }
}
