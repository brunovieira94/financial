<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;
use App\Scopes\ProfileCostCenterScope;
use ArrayObject;
use Config;

class PaymentRequest extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', '*'];
    protected static $logName = 'payment_request';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }
    use SoftDeletes;
    protected $table = 'payment_requests';
    protected $hidden = ['provider_id', 'bank_account_provider_id', 'business_id', 'cost_center_id', 'chart_of_account_id', 'currency_id', 'user_id'];
    protected $appends = ['applicant_can_edit', 'billet_link', 'invoice_link', 'xml_link', 'days_late', 'next_extension_date', 'next_competence_date'];

    protected $fillable = [
        'group_approval_flow_id',
        'company_id',
        'group_form_payment_id',
        'note',
        'percentage_discount',
        'provider_id',
        'emission_date',
        'pay_date',
        'bank_account_provider_id',
        'amount',
        'business_id',
        'cost_center_id',
        'chart_of_account_id',
        'currency_id',
        'exchange_rate',
        'frequency_of_installments',
        'invoice_number',
        'net_value',
        'bar_code',
        'invoice_file',
        'billet_file',
        'user_id',
        'xml_file',
        'invoice_type',
        'form_payment',
        'payment_type',
    ];

    public function group_approval_flow()
    {
        return $this->hasOne(GroupApprovalFlow::class, 'id', 'group_approval_flow_id')->with('approval_flow');
    }

    public function purchase_order()
    {
        return $this->hasMany(PaymentRequestHasPurchaseOrders::class, 'payment_request_id', 'id')->with(['purchase_order', 'purchase_order_installments']);
    }

    public function group_payment()
    {
        return $this->hasOne(GroupFormPayment::class, 'id', 'group_form_payment_id')->with('form_payment');
    }

    public function attachments()
    {
        return $this->hasMany(PaymentRequestHasAttachments::class, 'payment_request_id', 'id');
    }

    public function getXmlLinkAttribute()
    {
        if (!is_null($this->attributes['xml_file'])) {
            $XML = $this->attributes['xml_file'];
            return Storage::disk('s3')->temporaryUrl("XML/{$XML}", now()->addMinutes(30));
        }
    }

    public function getBilletLinkAttribute()
    {
        if (!is_null($this->attributes['billet_file'])) {
            $billet = $this->attributes['billet_file'];
            return Storage::disk('s3')->temporaryUrl("billet/{$billet}", now()->addMinutes(30));
        }
    }
    public function getInvoiceLinkAttribute()
    {
        if (!is_null($this->attributes['invoice_file'])) {
            $invoice = $this->attributes['invoice_file'];
            return Storage::disk('s3')->temporaryUrl("invoice/{$invoice}", now()->addMinutes(30));
        }
    }

    public function approval()
    {
        return $this->hasOne(AccountsPayableApprovalFlow::class, 'payment_request_id', 'id')->with('approval_flow');
    }

    public function installments()
    {
        return $this->hasMany(PaymentRequestHasInstallments::class, 'payment_request_id', 'id')->with(['group_payment', 'bank_account_provider', 'cnab_generated_installment'])->orderBy('parcel_number', 'asc');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class, 'id', 'provider_id')->with(['city', 'bank_account', 'user', 'provider_category', 'chart_of_account', 'cost_center']);
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id')->with(['bank_account', 'managers', 'city']);
    }

    public function bank_account_provider()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_provider_id')->with('bank');
    }

    public function business()
    {
        return $this->hasOne(Business::class, 'id', 'business_id');
    }

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

    public function chart_of_accounts()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'chart_of_account_id');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function tax()
    {
        return $this->hasMany(PaymentRequestHasTax::class, 'payment_request_id', 'id')->with('typeOfTax');
    }

    public function cnab_payment_request()
    {
        return $this->hasOne(CnabGeneratedHasPaymentRequests::class, 'payment_request_id', 'id')->with('cnab_generated')->orderBy('id', 'asc');
    }

    public function getDaysLateAttribute()
    {

        $installments = PaymentRequestHasInstallments::where('payment_request_id', $this->id)
            ->orderBy('extension_date', 'asc')
            ->get();

        foreach ($installments as $installment) {
            if ($installment['status'] != Config::get('constants.status.paid out')) {
                if ($installment['extension_date'] != NULL) {
                    $daysLate = date_diff(date_create($installment['extension_date']), now());
                    return $daysLate->days;
                    break;
                }
            }
        }
        return 0;
    }

    public function getNextExtensionDateAttribute()
    {
        return $this->installments->sortBy('extension_date')->where('status', '<>', Config::get('constants.status.paid out'))->first()->extension_date ?? null;
    }

    public function getNextCompetenceDateAttribute()
    {
        return $this->installments->sortBy('extension_date')->where('status', '<>', Config::get('constants.status.paid out'))->first()->competence_date ?? null;
    }

    public function getApplicantCanEditAttribute()
    {
        if (isset($this->approval)) {
            if ($this->approval->order == 1 && $this->approval->status == 0) {
                return true;
            } else if ($this->approval->order == 0) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($installmentPurchase) {
            if (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $installmentPurchase->id)->exists()) {
                foreach (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $installmentPurchase->id)->get() as $paymentRequestHasPurchaseOrderInstallments) {
                    if (PurchaseOrderHasInstallments::where('id', $paymentRequestHasPurchaseOrderInstallments['purchase_order_has_installments_id'])->exists()) {
                        $installmentPurchaseOrder = PurchaseOrderHasInstallments::findOrFail($paymentRequestHasPurchaseOrderInstallments['purchase_order_has_installments_id']);
                        $installmentPurchaseOrder->amount_paid -= $paymentRequestHasPurchaseOrderInstallments['amount_received'];
                        $installmentPurchaseOrder->save();
                    }
                }
                PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $installmentPurchase->id)->delete();
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope(new ProfileCostCenterScope);
    }
}
