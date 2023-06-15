<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PaymentRequestHasInstallmentsClean extends Model
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
    protected $fillable = ['client_identifier', 'client_name', 'card_identifier', 'text_cnab', 'status_cnab_code', 'type_billet', 'billet_file', 'fine', 'billet_number', 'bar_code', 'group_form_payment_id', 'bank_account_provider_id', 'percentage_discount', 'initial_value', 'discount', 'fees', 'extension_date', 'competence_date', 'parcel_number', 'payment_request_id', 'due_date', 'note', 'portion_amount', 'status', 'amount_received', 'verification_period', 'reference_number', 'revenue_code', 'tax_file_phone_number', 'payment_made_date', 'paid_value', 'bank_account_company_id', 'group_form_payment_made_id', 'system_payment_method', 'verification_period'];
    protected $appends = ['billet_link', 'latest_other_payment', 'linked_account'];
    protected $casts = [
        'verification_period' => AsCollection::class
    ];

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
        return $this->hasOne(CnabPaymentRequestsHasInstallments::class, 'installment_id', 'id')->orderBy('id', 'asc'); //->with('generated_cnab')->orderBy('id', 'asc');
    }

    public function other_payments()
    {
        return $this->belongsToMany(OtherPayment::class, 'payment_request_installments_have_other_payments', 'other_payment_id', 'payment_request_installment_id');
    }

    public function cnab_generated_installment_all()
    {
        return $this->hasMany(CnabPaymentRequestsHasInstallments::class, 'installment_id', 'id')->orderBy('id', 'asc');//->with('generated_cnab')->orderBy('id', 'asc');
    }

    public function getBilletLinkAttribute()
    {
        if (!is_null($this->attributes['billet_file'])) {
            $billet = $this->attributes['billet_file'];
            return Storage::disk('s3')->temporaryUrl("billet/{$billet}", now()->addMinutes(30));
        }
    }

    public function getLinkedAccountAttribute()
    {
        if ($this->linked) {
            $linked = PaymentRequestHasInstallmentLinked::where('payment_requests_installment_id', $this->id)->first();
            if ($linked != null) {
                return $linked->payment_request_id;
            }
        }

        return null;
    }

    public function getLatestOtherPaymentAttribute()
    {
        $latestCreationDate = $this->other_payments()->get()->max('created_at');
        $arr = $this->other_payments()->where('created_at', $latestCreationDate)->get();
        return empty($arr) ? null : $arr->first();
    }

    public function bank_account_company()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_company_id')->with('bank');
    }

    public function group_payment_received()
    {
        return $this->hasOne(GroupFormPayment::class, 'id', 'group_form_payment_made_id');
    }
}
