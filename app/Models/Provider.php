<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Provider extends Model
{
    use SoftDeletes;
    protected $table='providers';
    protected $casts = [
        'phones' => 'array',
    ];
    protected $fillable = ['company_name', 'trade_name', 'cpnj', 'responsible', 'provider_categories_id', 'cost_center_id', 'cep', 'cities_id', 'address', 'number', 'complement', 'district', 'phones', 'email'];

    public function bankAccount()
    {
        return $this->belongsToMany(BankAccount::class, 'provider_has_bank_accounts', 'provider_id', 'bank_account_id');
    }
}
