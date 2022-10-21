<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Billing extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['user', 'cangooroo', 'reason_to_reject', 'approval_flow', 'bank_account', 'billing_payment', '*'];
    protected static $logName = 'billing';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    // Model attributes
    use SoftDeletes;
    protected $table = 'billing';
    protected $fillable = [
        'cangooroo_booking_id',
        'reserve',
        'supplier_value',
        'pay_date',
        'boleto_value',
        'boleto_code',
        'recipient_name',
        'remark',
        'oracle_protocol',
        'user_id',
        'payment_status',
        'status_123',
        'cnpj',
        'approval_status',
        'reason',
        'reason_to_reject_id',
        'order',
        'suggestion',
        'suggestion_reason',
        'form_of_payment',
        'cangooroo_service_id',
        'bank_account_id',
        'pax_in_house',
        'billing_payment_id'
    ];
    protected $hidden = ['bank_account_id', 'user_id', 'cangooroo_booking_id', 'reason_to_reject_id', 'cangooroo_service_id', 'billing_payment_id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function cangooroo()
    {
        return $this->hasOne(Cangooroo::class, 'service_id', 'cangooroo_service_id')->with('hotel');
    }

    public function approval_flow()
    {
        return $this->hasOne(HotelApprovalFlow::class, 'order', 'order')->with('role')->latest();
    }

    public function reason_to_reject()
    {
        return $this->hasOne(HotelReasonToReject::class, 'id', 'reason_to_reject_id')->withTrashed();
    }

    public function bank_account()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_id')->with(['bank']);
    }

    public function billing_payment()
    {
        return $this->hasOne(BillingPayment::class, 'id', 'billing_payment_id')->with(['billings']);
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($billing) {
            $billing->cangooroo()->delete();
        });
    }

    public array $formsOfPayment = [
        "Boleto",
        "Pix",
        "Ted",
    ];
}
