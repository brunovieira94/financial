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
    protected static $logAttributes = ['bank_account', 'payment_type', '*'];
    protected static $logName = 'hotels';
    protected $hidden = ['payment_type_id'];
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='hotels';
    protected $fillable = ['id_hotel_cangooroo', 'id_hotel_omnibees', 'hotel_name', 'chain', 'email', 'email_omnibees', 'phone', 'billing_type', 'payment_type_id', 'holder_full_name', 'cpf_cnpj', 'bank_account_id', 'is_valid'];

    public function bank_account()
    {
        return $this->belongsToMany(BankAccount::class, 'hotel_has_bank_accounts', 'hotel_id', 'bank_account_id')->with(['bank', 'bank_account_default']);
    }

    public function payment_type()
    {
        return $this->hasOne(PaymentType::class, 'id', 'payment_type_id');
    }
}
