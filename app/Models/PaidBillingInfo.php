<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PaidBillingInfo extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['user_id','*'];
    protected static $logName = 'paid_billing_info';
    // public function tapActivity(Activity $activity)
    // {
    //     $user = auth()->user();
    //     $activity->causer_id = $user->id;
    //     $activity->causer_object = $user;
    // }

    // Model attributes
    use SoftDeletes;
    protected $table = 'paid_billing_info';
    protected $fillable = [
        'reserve',
        'operator',
        'supplier_value',
        'pay_date',
        'boleto_value',
        'boleto_code',
        'remark',
        'oracle_protocol',
        'user_id',
        'bank',
        'bank_code',
        'agency',
        'account',
        'form_of_payment',
        'hotel_name',
        'cnpj_hotel',
        'payment_voucher',
        'payment_method',
        'payment_bank',
        'payment_remark',
        'created_at',
        'service_id',
        'client_name'
    ];

    protected $hidden = ['user_id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
