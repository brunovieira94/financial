<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class BankAccount extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['bank', '*'];
    protected static $logName = 'bank_accounts';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $hidden = ['pivot', 'bank_id'];
    protected $table = 'bank_accounts';
    protected $fillable = ['cpf_cnpj' ,'entity_name', 'entity_type', 'hidden', 'covenant', 'agency_number', 'agency_check_number', 'account_number', 'account_check_number', 'bank_id', 'pix_key', 'account_type', 'pix_key_type'];

    public function bank()
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id')->with('form_payment')->withTrashed();
    }

    public function bank_account_default()
    {
        return $this->hasOne(ProviderHasBankAccounts::class, 'bank_account_id', 'id');
    }

    public function hotel_bank_account_default()
    {
        return $this->hasOne(HotelHasBankAccounts::class, 'bank_account_id', 'id');
    }

    public function bank_account_default_company()
    {
        return $this->hasOne(CompanyHasBankAccount::class, 'bank_account_id', 'id');
    }

    public array $accountTypes = [
        "Poupança",
        "Conta Corrente",
        "Conta Salário",
    ];

    public array $accountTypesTransfeera = [
        "POUPANÇA",
        "CORRENTE",
    ];
}
