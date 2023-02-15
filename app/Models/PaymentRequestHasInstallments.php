<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PaymentRequestHasInstallments extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['payment_request', 'group_payment', 'cnab_generated_installment',   '*'];
    protected static $logName = 'payment_request_installments';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    protected $table = 'payment_requests_installments';
    public $timestamps = false;
    protected $fillable = ['card_identifier', 'text_cnab', 'status_cnab_code', 'type_billet', 'billet_file', 'fine', 'billet_number', 'bar_code', 'group_form_payment_id', 'bank_account_provider_id', 'percentage_discount', 'initial_value', 'discount', 'fees', 'extension_date', 'competence_date', 'parcel_number', 'payment_request_id', 'due_date', 'note', 'portion_amount', 'status', 'amount_received'];
    protected $appends = ['billet_link'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['currency_old', 'provider', 'company', 'purchase_order', 'group_payment', 'attachments', 'approval', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax']);
    }

    public function group_payment()
    {
        return $this->hasOne(GroupFormPayment::class, 'id', 'group_form_payment_id')->with(['form_payment']);
    }

    public function bank_account_provider()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_provider_id')->with('bank');
    }

    public function cnab_generated_installment()
    {
        return $this->hasOne(CnabPaymentRequestsHasInstallments::class, 'installment_id', 'id')->with('generated_cnab')->orderBy('id', 'asc');
    }

    public function other_payments()
    {
        return $this->belongsToMany(OtherPayment::class, 'payment_request_installments_have_other_payments', 'other_payment_id', 'payment_request_installment_id');
    }

    public function getBilletLinkAttribute()
    {
        if (!is_null($this->attributes['billet_file'])) {
            $billet = $this->attributes['billet_file'];
            return Storage::disk('s3')->temporaryUrl("billet/{$billet}", now()->addMinutes(30));
        }
    }
}
