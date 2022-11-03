<?php

namespace App\Models;

use App\Services\BillingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Config;

class BillingPayment extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['billings', '*'];
    protected static $logName = 'billing_payments';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    // Model attributes
    use SoftDeletes;
    protected $table = 'billing_payments';
    protected $fillable = [
        'pay_date',
        'boleto_value',
        'boleto_code',
        'recipient_name',
        'oracle_protocol',
        'status',
        'cnpj',
        'form_of_payment',
        'hotel_id',
    ];

    protected $appends = ['ready_to_pay', 'invoiced_value'];

    public function billings()
    {
        return $this->hasMany(Billing::class, 'billing_payment_id', 'id')->with(['bank_account', 'user', 'cangooroo', 'reason_to_reject', 'approval_flow']);
    }

    public function hotel()
    {
        return $this->hasOne(Hotel::class, 'id_hotel_cangooroo', 'hotel_id');
    }

    public function getReadyToPayAttribute()
    {
        if($this->form_of_payment == 0){
            $sum = 0;
            foreach ($this->billings as $billing){
                $sum += $billing->supplier_value;
            }
            if($this->status == Config::get('constants.billingStatus.approved') && $sum == $this->boleto_value) return true;
        }
        else{
            if($this->status == Config::get('constants.billingStatus.approved')) return true;
        }
        return false;
    }

    public function getInvoicedValueAttribute()
    {
        $sum = 0;
        foreach ($this->billings as $billing){
            $sum += $billing->supplier_value;
        }
        return $sum;
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($billingPayment) {
            foreach ($billingPayment->billings as $billing) {
                $billing = Billing::findOrFail($billing->id);
                $billing->billing_payment_id = null;
                $billing->approval_status =  Config::get('constants.billingStatus.canceled');
                $billing->save();
            }
        });
    }

    public array $formsOfPayment = [
        "Boleto",
        "Pix",
        "Ted",
    ];
}
