<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Hotel extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['bank_account', 'group_payment', '*'];
    protected static $logName = 'hotels';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table = 'hotels';
    protected $fillable = [
        'id_hotel_cangooroo',
        'id_hotel_omnibees',
        'hotel_name',
        'chain',
        'email',
        'email_omnibees',
        'phone',
        'billing_type',
        'group_form_payment_id',
        'holder_full_name',
        'cpf_cnpj',
        'bank_account_id',
        'is_valid',
    ];
    protected $hidden = ['bank_account_id', 'group_form_payment_id'];

    public function bank_account()
    {
        return $this->belongsToMany(BankAccount::class, 'hotel_has_bank_accounts', 'hotel_id', 'bank_account_id')->with(['bank', 'hotel_bank_account_default']);
    }

    public function group_payment()
    {
        return $this->hasOne(GroupFormPayment::class, 'id', 'group_form_payment_id')->with('form_payment');
    }
}
