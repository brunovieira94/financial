<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelHasBankAccounts extends Model
{
    protected $table='hotel_has_bank_accounts';
    public $timestamps = false;
    protected $fillable = ['hotel_id', 'bank_account_id', 'default_bank'];
    protected $hidden = ['hotel_id'];

    public function bank_account()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_id')->with('bank');
    }

}
